The task is now fully complete.

I have successfully re-integrated the "Hasil Analisis" (statistics) and "Peta Sebaran Titik" (map) menus, making them fully dynamic to match the content of the uploaded file.

**Summary of Final Changes:**

1.  **Dynamic Statistics:** The "Hasil Analisis" section will now automatically detect columns with names related to height, distance, etc., and display their Minimum, Average, and Maximum values.
2.  **Dynamic Map:**
    *   The map is back. It will automatically detect Latitude and Longitude columns from your file.
    *   If coordinate columns are found, all data points will be plotted on the map.
    *   You can click on any point on the map to see all the data for that point in a small pop-up.
3.  **Interactive Table:** The rows in the "Data LiDAR" table are now clickable. Clicking a row will automatically move the map to focus on that point's location.

The entire analysis and visualization process is now flexible and adapts to the structure of the data file you provide.