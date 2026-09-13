<!DOCTYPE html>
<html>
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
</head>
<body>

    <div>
        <h1>Welcome to My Excel Export</h1>
        <p>This is some content in your HTML view.</p>
        <!-- Add more HTML content as needed -->
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            exportToExcel(data);
        });

        function exportToExcel(data) {
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.json_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

            var headerRow = ["Header1", "Header2", "HeaderImage"];
            XLSX.utils.sheet_add_aoa(ws, [headerRow], { origin: -1 });

            var headerImageURL = "https://example.com/path/to/online_image.jpg";
            ws['C1'] = { t: 's', v: '', p: { patternType: 'none', fgColor: { theme: 8 } }, s: { fill: { patternType: 'none', fgColor: { theme: 8 } } } };
            ws['C1']['c'] = [{ t: 's', v: headerImageURL, p: { patternType: 'stretch', fgColor: { theme: 8 } } }];

            var blob = XLSX.write(wb, { bookType: 'xlsx', type: 'blob' });

            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = 'filename.xlsx';
            link.click();
        }

        var data = [
            { "Column1": "Value1", "Column2": "Value2", "Image": "path/to/image1.jpg" },
            { "Column1": "Value3", "Column2": "Value4", "Image": "path/to/image2.jpg" },
            // Add more rows as needed
        ];
    </script>
</body>
</html>
