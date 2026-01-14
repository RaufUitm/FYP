Quick install instructions for the Fault Prediction project

Windows (PowerShell):

1. Open PowerShell and change to project directory:

```powershell
cd C:\laragon\www\fault-prediction-system
```

2. Create and activate a virtual environment (recommended):

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
```

3. Upgrade pip and install dependencies:

```powershell
python -m pip install --upgrade pip
pip install -r requirements.txt
```

4. Run the API server:

```powershell
python app.py

Database setup (MySQL / MariaDB)

This project expects a MySQL-compatible database named `fault_prediction_db` by default. You can create it using the included SQL file.

From PowerShell (using the MySQL client):

```powershell
cd C:\laragon\www\fault-prediction-system
mysql -u root -p < create_db.sql
```

If you prefer phpMyAdmin (Laragon), open phpMyAdmin, choose "Import" and upload `create_db.sql`, or paste the SQL into the SQL window and execute.

If your MySQL user or password is different, update the database connection in `config.php` before running the app:

- File: config.php
- Edit `$host`, `$dbname`, `$username`, `$password` to match your environment.

Notes:
- The SQL creates two tables: `users` and `prediction_history`.
- `prediction_history` has a unique index on `(user_id, file_name)` so re-submitting the same file for the same user updates the record.
- If you get permission errors, run the MySQL client as an administrator or use phpMyAdmin.
```

Notes:
- The web frontend is PHP and expects to run under your local web server (Laragon/XAMPP). Ensure PHP/Apache is running and the project is served at http://localhost/fault-prediction-system.
- The saved model file is in `JN/trained_models/fault_prediction_decision_tree_model.joblib`. If model loading fails, you may need to install the same `scikit-learn` version used when the model was trained.
- To install a specific scikit-learn version (if needed):

```powershell
pip install scikit-learn==1.0.2
```

Troubleshooting:
- If `joblib.load` raises errors about missing classes, install the same scikit-learn version used for training.
- For large CSV files, ensure enough memory or process in chunks.
