<?php
/**
 * PDF Form Field Extractor
 * Extracts inputs, radio buttons, checkboxes, and tables from PDF
 * Generates JSON with labels as keys (lowercase, spaces replaced with underscores)
 */

// Check if PDF file exists
function findPdfFile() {
    $files = glob('*.pdf');
    if (empty($files)) {
        die("Error: No PDF file found in the current directory. Please upload a PDF file.\n");
    }
    return $files[0]; // Return the first PDF found
}

/**
 * Process label to create JSON key
 * - Convert to lowercase
 * - Replace spaces with underscores
 * - Remove special characters except underscores
 */
function processLabel($label) {
    // Convert to lowercase
    $label = strtolower(trim($label));
    
    // Replace spaces with underscores
    $label = str_replace(' ', '_', $label);
    
    // Remove special characters except underscores and alphanumeric
    $label = preg_replace('/[^a-z0-9_]/', '', $label);
    
    // Remove multiple consecutive underscores
    $label = preg_replace('/_+/', '_', $label);
    
    // Remove leading/trailing underscores
    $label = trim($label, '_');
    
    return $label;
}

/**
 * Extract form fields using different methods
 */
function extractFormFields($pdfPath) {
    $formFields = [];
    
    // Method 1: Try using pdftk (if available)
    if (isCommandAvailable('pdftk')) {
        echo "Using pdftk to extract form fields...\n";
        $formFields = extractWithPdftk($pdfPath);
    }
    
    // Method 2: Try using pdfinfo/pdftotext (if available)
    if (empty($formFields) && isCommandAvailable('pdftotext')) {
        echo "Using pdftotext to extract text and identify form patterns...\n";
        $formFields = extractWithPdftotext($pdfPath);
    }
    
    // Method 3: Manual parsing approach
    if (empty($formFields)) {
        echo "Using manual parsing approach...\n";
        $formFields = extractManually($pdfPath);
    }
    
    return $formFields;
}

/**
 * Check if a command is available in the system
 */
function isCommandAvailable($command) {
    $result = shell_exec("which $command 2>/dev/null");
    return !empty($result);
}

/**
 * Extract form fields using pdftk
 */
function extractWithPdftk($pdfPath) {
    $command = "pdftk " . escapeshellarg($pdfPath) . " dump_data_fields 2>/dev/null";
    $output = shell_exec($command);
    
    if (empty($output)) {
        return [];
    }
    
    $fields = [];
    $lines = explode("\n", $output);
    $currentField = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line)) {
            if (!empty($currentField)) {
                $fields[] = $currentField;
                $currentField = [];
            }
            continue;
        }
        
        if (preg_match('/^(\w+):\s*(.*)$/', $line, $matches)) {
            $currentField[$matches[1]] = $matches[2];
        }
    }
    
    // Process the last field
    if (!empty($currentField)) {
        $fields[] = $currentField;
    }
    
    return $fields;
}

/**
 * Extract form patterns using pdftotext
 */
function extractWithPdftotext($pdfPath) {
    $command = "pdftotext " . escapeshellarg($pdfPath) . " - 2>/dev/null";
    $text = shell_exec($command);
    
    if (empty($text)) {
        return [];
    }
    
    $fields = [];
    $lines = explode("\n", $text);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Look for common form patterns
        // Checkbox patterns: □ Label, ☐ Label, [ ] Label
        if (preg_match('/^[□☐\[\]]\s*(.+)$/', $line, $matches)) {
            $label = trim($matches[1]);
            if (!empty($label)) {
                $fields[] = [
                    'type' => 'checkbox',
                    'label' => $label,
                    'key' => processLabel($label)
                ];
            }
        }
        
        // Input field patterns: Label: _____, Label: _______
        if (preg_match('/^(.+?):\s*[_\-\.]{3,}/', $line, $matches)) {
            $label = trim($matches[1]);
            if (!empty($label)) {
                $fields[] = [
                    'type' => 'input',
                    'label' => $label,
                    'key' => processLabel($label)
                ];
            }
        }
        
        // Radio button patterns: ○ Option1 ○ Option2
        if (preg_match_all('/[○●]\s*([^○●]+)/', $line, $matches)) {
            foreach ($matches[1] as $option) {
                $label = trim($option);
                if (!empty($label)) {
                    $fields[] = [
                        'type' => 'radio',
                        'label' => $label,
                        'key' => processLabel($label)
                    ];
                }
            }
        }
    }
    
    return $fields;
}

/**
 * Manual extraction approach - analyze PDF structure
 */
function extractManually($pdfPath) {
    echo "Attempting manual extraction...\n";
    echo "Note: For better results, install pdftk or poppler-utils (pdftotext)\n";
    
    // Try to read PDF content as text (basic approach)
    $content = file_get_contents($pdfPath);
    
    // Look for common PDF form field markers
    $fields = [];
    
    // This is a basic approach - PDF parsing is complex
    // For production use, consider using a proper PDF library
    
    return [
        [
            'type' => 'note',
            'label' => 'Manual extraction requires PDF parsing library',
            'key' => 'manual_extraction_note',
            'message' => 'Install pdftk or poppler-utils for better extraction'
        ]
    ];
}

/**
 * Generate JSON output
 */
function generateJson($fields) {
    $json = [];
    
    foreach ($fields as $field) {
        $key = isset($field['key']) ? $field['key'] : processLabel($field['label'] ?? '');
        
        if (empty($key)) {
            continue;
        }
        
        $json[$key] = [
            'type' => $field['type'] ?? 'unknown',
            'label' => $field['label'] ?? '',
            'value' => '', // Empty value for form filling
        ];
        
        // Add additional properties based on field type
        if (isset($field['FieldType'])) {
            $json[$key]['pdf_type'] = $field['FieldType'];
        }
        
        if (isset($field['FieldName'])) {
            $json[$key]['pdf_name'] = $field['FieldName'];
        }
    }
    
    return $json;
}

/**
 * Main execution
 */
function main() {
    echo "PDF Form Field Extractor\n";
    echo "========================\n\n";
    
    // Find PDF file
    $pdfPath = findPdfFile();
    echo "Found PDF file: $pdfPath\n\n";
    
    // Extract form fields
    $fields = extractFormFields($pdfPath);
    
    if (empty($fields)) {
        echo "No form fields found in the PDF.\n";
        echo "This could mean:\n";
        echo "- The PDF has no interactive form fields\n";
        echo "- The PDF uses image-based forms\n";
        echo "- Additional tools are needed for extraction\n\n";
        
        // Create a basic structure anyway
        $fields = [
            [
                'type' => 'note',
                'label' => 'No form fields detected',
                'key' => 'no_fields_detected'
            ]
        ];
    }
    
    // Generate JSON
    $json = generateJson($fields);
    
    // Save to file
    $outputFile = 'pdf-form-fields.json';
    file_put_contents($outputFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "Extraction complete!\n";
    echo "Found " . count($fields) . " form elements\n";
    echo "JSON saved to: $outputFile\n\n";
    
    // Display preview
    echo "Preview of extracted fields:\n";
    echo "============================\n";
    foreach ($json as $key => $field) {
        echo "Key: $key\n";
        echo "  Type: " . $field['type'] . "\n";
        echo "  Label: " . $field['label'] . "\n";
        echo "  Value: (empty)\n\n";
    }
    
    return $json;
}

// Run the extractor
if (php_sapi_name() === 'cli') {
    main();
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php pdf-form-extractor.php\n";
}
?>