# app.py - Enhanced version with CORS support

import os
import pandas as pd
import joblib
from flask import Flask, request, jsonify
from flask_cors import CORS

# --- Configuration ---
# Define the directory where your trained model is saved
MODEL_DIR = 'JN\\trained_models'
MODEL_FILENAME = os.path.join(MODEL_DIR, 'fault_prediction_decision_tree_model.joblib')

# Define the same features used during training
# This is crucial: incoming data for prediction MUST have these columns
FEATURES = ['wmc', 'dit', 'noc', 'cbo', 'rfc', 'lcom', 'ca', 'ce', 'npm',
            'lcom3', 'loc', 'dam', 'moa', 'mfa', 'cam', 'ic', 'cbm', 'amc',
            'max_cc', 'avg_cc']

# --- Initialize Flask App ---
app = Flask(__name__)

# Enable CORS for all routes to allow the PHP frontend to make requests
CORS(app, origins=['*'])  # In production, specify your domain instead of '*'

# --- Load the Trained Model Globally ---
# Load the model once when the app starts to avoid reloading for every request
try:
    LOADED_MODEL = joblib.load(MODEL_FILENAME)
    print(f"Model loaded successfully from: {MODEL_FILENAME}")
except FileNotFoundError:
    print(f"Error: Model file not found at {MODEL_FILENAME}. Please ensure it exists.")
    LOADED_MODEL = None # Set to None to indicate model loading failure
except Exception as e:
    print(f"Error loading model: {e}")
    LOADED_MODEL = None

# --- API Endpoint for Fault Prediction ---
@app.route('/predict_fault', methods=['POST'])
def predict_fault():
    # 1. Check if the model was loaded successfully
    if LOADED_MODEL is None:
        return jsonify({"error": "Prediction model not available. Please check server logs."}), 500

    # 2. Check for file in the request
    if 'file' not in request.files:
        return jsonify({"error": "No file part in the request"}), 400

    file = request.files['file']

    # 3. Check if a file was selected
    if file.filename == '':
        return jsonify({"error": "No selected file"}), 400

    # 4. Process the file
    if file and file.filename.endswith('.csv'):
        try:
            # Read the CSV file directly into a pandas DataFrame
            new_data = pd.read_csv(file)
            
            # Log the incoming data structure for debugging
            print(f"Received CSV with columns: {list(new_data.columns)}")
            print(f"Number of rows: {len(new_data)}")

            # Check if all required features are present
            missing_features = [f for f in FEATURES if f not in new_data.columns]
            if missing_features:
                return jsonify({
                    "error": f"Input CSV is missing required columns: {', '.join(missing_features)}",
                    "required_columns": FEATURES,
                    "provided_columns": list(new_data.columns)
                }), 400

            # Select only the features for prediction
            X_new = new_data[FEATURES]

            # Make predictions
            predictions = LOADED_MODEL.predict(X_new)
            
            # Get prediction probabilities if available
            try:
                prediction_proba = LOADED_MODEL.predict_proba(X_new)
                # Assuming binary classification (0: Not Faulty, 1: Faulty)
                fault_probabilities = prediction_proba[:, 1] if prediction_proba.shape[1] > 1 else None
            except:
                fault_probabilities = None

            # Add predictions back to the original new_data DataFrame
            new_data['predicted_faulty'] = predictions
            new_data['prediction_label'] = new_data['predicted_faulty'].map({0: 'Healthy', 1: 'Faulty'})
            
            if fault_probabilities is not None:
                new_data['fault_probability'] = fault_probabilities
                new_data['confidence'] = new_data['fault_probability'].apply(
                    lambda x: f"{x*100:.1f}%" if x >= 0.5 else f"{(1-x)*100:.1f}%"
                )

            # Prepare response data
            results = []
            for idx, row in new_data.iterrows():
                result_item = {
                    'index': idx,
                    'prediction_label': row['prediction_label'],
                    'predicted_faulty': int(row['predicted_faulty'])
                }
                
                # Add identifier if available (check multiple possible column names)
                identifier_columns = ['name.1', 'name', 'class_name', 'module_name', 'file_name']
                for col in identifier_columns:
                    if col in new_data.columns:
                        result_item[col] = row[col]
                        break
                else:
                    result_item['name.1'] = f"Item_{idx + 1}"
                
                # Add confidence if available
                if 'confidence' in new_data.columns:
                    result_item['confidence'] = row['confidence']
                
                # Add fault probability if available
                if 'fault_probability' in new_data.columns:
                    result_item['fault_probability'] = float(row['fault_probability'])
                
                results.append(result_item)

            # Summary statistics
            total_items = len(results)
            faulty_items = sum(1 for r in results if r['predicted_faulty'] == 1)
            healthy_items = total_items - faulty_items
            
            response_data = {
                "status": "success",
                "predictions": results,
                "summary": {
                    "total_items": total_items,
                    "faulty_items": faulty_items,
                    "healthy_items": healthy_items,
                    "fault_percentage": f"{(faulty_items/total_items)*100:.1f}%" if total_items > 0 else "0%"
                }
            }

            return jsonify(response_data), 200

        except pd.errors.EmptyDataError:
            return jsonify({"error": "The uploaded CSV file is empty"}), 400
        except pd.errors.ParserError:
            return jsonify({"error": "Could not parse CSV file. Please ensure it is well-formatted."}), 400
        except Exception as e:
            # Catch any other unexpected errors during processing or prediction
            print(f"An unexpected error occurred: {e}")
            return jsonify({"error": f"An internal server error occurred during prediction: {str(e)}"}), 500
    else:
        return jsonify({"error": "Invalid file type. Please upload a CSV file."}), 400

# --- Health Check Endpoint ---
@app.route('/health', methods=['GET'])
def health_check():
    model_status = "loaded" if LOADED_MODEL is not None else "not_loaded"
    return jsonify({
        "status": "API is running",
        "model_status": model_status,
        "required_features": FEATURES
    }), 200

# --- Get Model Info Endpoint ---
@app.route('/model_info', methods=['GET'])
def model_info():
    if LOADED_MODEL is None:
        return jsonify({"error": "Model not loaded"}), 500
    
    try:
        model_type = type(LOADED_MODEL).__name__
        return jsonify({
            "model_type": model_type,
            "required_features": FEATURES,
            "feature_count": len(FEATURES)
        }), 200
    except Exception as e:
        return jsonify({"error": f"Error getting model info: {str(e)}"}), 500

if __name__ == '__main__':
    print("Starting Fault Prediction API...")
    print(f"Model file path: {MODEL_FILENAME}")
    print(f"Required features: {FEATURES}")

    import webbrowser
    import threading
    import os

    # Only open browser if not running in the Flask reloader process
    if os.environ.get('WERKZEUG_RUN_MAIN') == 'true':
        def open_php_frontend():
            webbrowser.open_new("http://localhost/fault-prediction-system/index.php")
        threading.Timer(1.5, open_php_frontend).start()

    app.run(debug=True, host='0.0.0.0', port=5000)

