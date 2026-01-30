This concludes the task. I have implemented the following changes:

1.  **Installed `maatwebsite/excel`:** Added the `maatwebsite/excel` package to the project to handle Excel and CSV file parsing.
2.  **Modified `LidarController.php`:**
    *   Updated the validation rules for `lidar_file` to accept `txt`, `csv`, and `xlsx` extensions.
    *   Refactored the file upload logic to detect the file type (CSV, XLSX, or TXT).
    *   For CSV and XLSX files, implemented parsing using `Maatwebsite\Excel\Facades\Excel::toCollection()`.
    *   For TXT files, retained the existing `fopen` and `fgets` line-by-line reading logic.
    *   Unified the subsequent data processing (coordinate conversion and object formatting) to work with a common `lines` array, ensuring that all supported file types are processed consistently and displayed in the "Sampel Data Objek LiDAR" table.

These changes ensure that the system can now accept and display data from Excel and CSV files in the "Sampel Data Objek LiDAR" menu as requested.