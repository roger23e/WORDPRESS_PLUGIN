<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Form Field Extractor</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 30px;
            margin: 20px 0;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
            background: #f8f9ff;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }
        
        .upload-area.dragover {
            border-color: #4caf50;
            background: #e8f5e8;
        }
        
        .file-input {
            display: none;
        }
        
        .upload-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        
        .upload-btn:hover {
            transform: translateY(-2px);
        }
        
        .results {
            margin-top: 30px;
        }
        
        .field-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }
        
        .field-type {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .field-key {
            font-family: 'Courier New', monospace;
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            color: #495057;
        }
        
        .json-output {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .copy-btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 0;
        }
        
        .copy-btn:hover {
            background: #45a049;
        }
        
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #667eea;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 PDF Form Field Extractor</h1>
        
        <div class="upload-area" id="uploadArea">
            <div style="font-size: 48px; margin-bottom: 15px;">📁</div>
            <h3>Upload your PDF file</h3>
            <p>Drag and drop a PDF file here or click to browse</p>
            <button class="upload-btn" onclick="document.getElementById('pdfFile').click()">
                Choose PDF File
            </button>
            <input type="file" id="pdfFile" class="file-input" accept=".pdf">
        </div>
        
        <div id="results" class="results" style="display: none;"></div>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('pdfFile');
        const results = document.getElementById('results');

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type === 'application/pdf') {
                handleFile(files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        function handleFile(file) {
            showLoading();
            
            const formData = new FormData();
            formData.append('pdf', file);
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                displayResults(data);
            })
            .catch(error => {
                showError('Error processing PDF: ' + error.message);
            });
        }

        function showLoading() {
            results.style.display = 'block';
            results.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <h3>Processing PDF...</h3>
                    <p>Extracting form fields and generating JSON...</p>
                </div>
            `;
        }

        function showError(message) {
            results.style.display = 'block';
            results.innerHTML = `<div class="error"><strong>Error:</strong> ${message}</div>`;
        }

        function displayResults(data) {
            if (data.error) {
                showError(data.error);
                return;
            }

            let html = `
                <div class="success">
                    <strong>Success!</strong> Found ${data.count} form fields in the PDF.
                </div>
                
                <h3>📋 Extracted Fields</h3>
            `;

            if (data.fields && Object.keys(data.fields).length > 0) {
                Object.entries(data.fields).forEach(([key, field]) => {
                    html += `
                        <div class="field-item">
                            <span class="field-type">${field.type}</span>
                            <strong>${field.label}</strong>
                            <br>
                            <small>Key: <span class="field-key">${key}</span></small>
                        </div>
                    `;
                });

                html += `
                    <h3>📝 Generated JSON</h3>
                    <button class="copy-btn" onclick="copyJson()">Copy JSON</button>
                    <pre class="json-output" id="jsonOutput">${JSON.stringify(data.fields, null, 2)}</pre>
                `;
            } else {
                html += `<div class="error">No form fields found in the PDF.</div>`;
            }

            results.innerHTML = html;
        }

        function copyJson() {
            const jsonOutput = document.getElementById('jsonOutput');
            const text = jsonOutput.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.style.background = '#4caf50';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '#4caf50';
                }, 2000);
            });
        }
    </script>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    header('Content-Type: application/json');
    
    try {
        $uploadedFile = $_FILES['pdf'];
        
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $uploadedFile['error']);
        }
        
        if ($uploadedFile['type'] !== 'application/pdf') {
            throw new Exception('Please upload a PDF file.');
        }
        
        $tempPath = $uploadedFile['tmp_name'];
        
        // Include the extraction functions
        include_once('pdf-form-extractor.php');
        
        // Extract form fields
        $fields = extractFormFields($tempPath);
        $json = generateJson($fields);
        
        // Save the JSON to a file
        file_put_contents('extracted-pdf-fields.json', json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode([
            'success' => true,
            'count' => count($fields),
            'fields' => $json,
            'message' => 'PDF processed successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}
?>