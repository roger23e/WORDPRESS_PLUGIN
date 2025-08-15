# 📄 PDF Form Field Extractor

Extract all form elements (inputs, radio buttons, checkboxes, and tables) from PDF files and generate JSON with processed labels as keys.

## ✨ Features

- **🔍 Multiple Extraction Methods**: Uses `pdftk`, `pdftotext`, and manual parsing
- **📝 Smart Label Processing**: Converts labels to lowercase and replaces spaces with underscores
- **🎯 Form Element Detection**: Finds inputs, checkboxes, radio buttons, and tables
- **🌐 Web Interface**: Beautiful drag-and-drop web interface
- **💻 Command Line**: CLI version for automation
- **📊 JSON Output**: Clean, structured JSON with processed keys

## 🚀 Quick Start

### 1. Upload Your PDF
Place your PDF file in the project directory.

### 2. Install Required Tools (Optional but Recommended)
```bash
# Make installation script executable
chmod +x install-pdf-tools.sh

# Run installation script
./install-pdf-tools.sh
```

### 3. Choose Your Method

#### Option A: Web Interface (Recommended)
1. Open `pdf-extractor-web.php` in your browser
2. Drag and drop your PDF file
3. Get instant results with beautiful visualization

#### Option B: Command Line
```bash
php pdf-form-extractor.php
```

## 📋 Example Output

### Input PDF with these fields:
- First Name: _______
- ☐ Subscribe to newsletter
- ○ Male ○ Female
- Email Address: _______

### Generated JSON:
```json
{
  "first_name": {
    "type": "input",
    "label": "First Name",
    "value": ""
  },
  "subscribe_to_newsletter": {
    "type": "checkbox", 
    "label": "Subscribe to newsletter",
    "value": ""
  },
  "male": {
    "type": "radio",
    "label": "Male", 
    "value": ""
  },
  "female": {
    "type": "radio",
    "label": "Female",
    "value": ""
  },
  "email_address": {
    "type": "input",
    "label": "Email Address",
    "value": ""
  }
}
```

## 🛠️ How It Works

### Label Processing Rules:
1. **Lowercase**: "First Name" → "first name"
2. **Replace Spaces**: "first name" → "first_name" 
3. **Remove Special Characters**: Keep only letters, numbers, underscores
4. **Clean Up**: Remove multiple underscores and trim

### Detection Methods:

#### Method 1: pdftk (Most Accurate)
- Extracts actual PDF form fields
- Works with interactive PDFs
- Provides field types and names

#### Method 2: pdftotext (Pattern Matching)
- Converts PDF to text
- Uses regex patterns to find form elements
- Detects: `□ Label`, `Label: ____`, `○ Option`

#### Method 3: Manual Parsing (Fallback)
- Basic PDF content analysis
- Used when other methods fail

## 📁 Files Created

- **`pdf-form-fields.json`** - Main JSON output
- **`extracted-pdf-fields.json`** - Web interface output

## 🔧 Installation Details

### Required Tools:
- **PHP** (for running the scripts)
- **pdftk** or **pdftk-java** (recommended)
- **poppler-utils** (`pdftotext`)
- **qpdf** (alternative)

### Supported Systems:
- ✅ Ubuntu/Debian
- ✅ CentOS/RHEL/Fedora  
- ✅ macOS (with Homebrew)
- ✅ Other Linux distributions (manual install)

## 🎨 Web Interface Features

- **📁 Drag & Drop**: Intuitive file upload
- **⚡ Real-time Processing**: Instant results
- **📋 Visual Field List**: See all extracted fields
- **📝 JSON Preview**: Formatted JSON output
- **📄 Copy to Clipboard**: One-click copy
- **🎯 Error Handling**: Clear error messages

## 🐛 Troubleshooting

### No Fields Found?
- PDF might not have interactive form fields
- PDF might be image-based (scanned)
- Try installing additional tools: `./install-pdf-tools.sh`

### Permission Errors?
- Ensure PHP has write permissions in the directory
- Check file permissions: `ls -la`

### Tool Not Found?
- Run installation script: `./install-pdf-tools.sh`
- Or install manually based on your OS

## 📊 Supported Form Elements

| Element Type | Detection Pattern | Example |
|--------------|------------------|---------|
| **Input Field** | `Label: ____` | `Name: _______` |
| **Checkbox** | `□ Label` or `☐ Label` | `☐ Subscribe` |
| **Radio Button** | `○ Option` or `● Option` | `○ Male ○ Female` |
| **Interactive Fields** | PDF form fields | Actual form elements |

## 🔮 Advanced Usage

### Custom Processing:
Modify the `processLabel()` function in `pdf-form-extractor.php` to customize key generation.

### Batch Processing:
```bash
# Process multiple PDFs
for file in *.pdf; do
    echo "Processing $file..."
    php pdf-form-extractor.php "$file"
done
```

### Integration:
Include the extractor functions in your PHP projects:
```php
include_once('pdf-form-extractor.php');
$fields = extractFormFields('path/to/file.pdf');
$json = generateJson($fields);
```

## 🤝 Contributing

Found a bug or want to improve the extractor? Feel free to:
- Report issues
- Suggest improvements
- Add support for more PDF types
- Improve pattern detection

## 📄 License

This project is open source. Use it freely for your PDF form extraction needs!

---

**Happy Extracting!** 🎉

Place your PDF in the directory and start extracting form fields with clean, processed JSON keys!