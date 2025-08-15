<?php
/**
 * Create Test PDF with Form Fields
 * This creates a sample PDF to demonstrate the form field extractor
 */

// Simple HTML to PDF content
$htmlContent = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sample Form</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .form-field { margin: 15px 0; }
        .checkbox { margin: 5px 0; }
        .radio-group { margin: 10px 0; }
        input[type="text"] { border: none; border-bottom: 2px solid #000; width: 200px; }
    </style>
</head>
<body>
    <h1>Sample Registration Form</h1>
    
    <div class="form-field">
        <label>First Name: ________________</label>
    </div>
    
    <div class="form-field">
        <label>Last Name: ________________</label>
    </div>
    
    <div class="form-field">
        <label>Email Address: ________________</label>
    </div>
    
    <div class="form-field">
        <label>Phone Number: ________________</label>
    </div>
    
    <div class="form-field">
        <label>Date of Birth: ________________</label>
    </div>
    
    <h3>Gender:</h3>
    <div class="radio-group">
        ○ Male<br>
        ○ Female<br>
        ○ Other
    </div>
    
    <h3>Interests (Check all that apply):</h3>
    <div class="checkbox">
        □ Sports<br>
        □ Music<br>
        □ Technology<br>
        □ Travel<br>
        □ Reading<br>
        □ Cooking
    </div>
    
    <h3>Subscription Preferences:</h3>
    <div class="checkbox">
        □ Newsletter subscription<br>
        □ Product updates<br>
        □ Event notifications<br>
        □ Marketing communications
    </div>
    
    <div class="form-field">
        <label>Additional Comments: ________________</label>
    </div>
    
    <div class="form-field">
        <label>Emergency Contact: ________________</label>
    </div>
    
    <div class="form-field">
        <label>Company Name: ________________</label>
    </div>
    
    <div class="form-field">
        <label>Job Title: ________________</label>
    </div>
</body>
</html>
';

// Save HTML file
file_put_contents('/workspace/test-form.html', $htmlContent);

echo "✅ Test HTML form created: test-form.html\n";
echo "\n";
echo "To convert to PDF, you can:\n";
echo "1. Open test-form.html in your browser and print to PDF\n";
echo "2. Use wkhtmltopdf if available: wkhtmltopdf test-form.html test-form.pdf\n";
echo "3. Upload your own PDF to test the extractor\n";
echo "\n";
echo "The HTML contains these form elements:\n";
echo "- Input fields: First Name, Last Name, Email, Phone, etc.\n";
echo "- Radio buttons: Gender selection\n";
echo "- Checkboxes: Interests and subscription preferences\n";
echo "\n";
echo "Expected JSON keys after processing:\n";
echo "- first_name, last_name, email_address\n";
echo "- male, female, other\n";
echo "- sports, music, technology, travel, reading, cooking\n";
echo "- newsletter_subscription, product_updates, etc.\n";
?>