#!/bin/bash

echo "PDF Form Extractor - Tool Installation"
echo "======================================"
echo ""

# Function to check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        echo "⚠️  Running as root. This is not recommended."
        echo "   Please run this script as a regular user."
        exit 1
    fi
}

# Function to detect OS
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        if command -v apt-get &> /dev/null; then
            OS="ubuntu"
        elif command -v yum &> /dev/null; then
            OS="centos"
        elif command -v dnf &> /dev/null; then
            OS="fedora"
        else
            OS="linux"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
    else
        OS="unknown"
    fi
}

# Function to install tools on Ubuntu/Debian
install_ubuntu() {
    echo "📦 Installing PDF tools on Ubuntu/Debian..."
    
    # Update package list
    sudo apt-get update
    
    # Install poppler-utils (includes pdftotext)
    echo "Installing poppler-utils..."
    sudo apt-get install -y poppler-utils
    
    # Install pdftk (if available)
    echo "Installing pdftk..."
    if sudo apt-get install -y pdftk 2>/dev/null; then
        echo "✅ pdftk installed successfully"
    else
        echo "⚠️  pdftk not available in default repos, trying pdftk-java..."
        if sudo apt-get install -y pdftk-java 2>/dev/null; then
            echo "✅ pdftk-java installed successfully"
        else
            echo "❌ Could not install pdftk. Will use alternative methods."
        fi
    fi
    
    # Install qpdf as alternative
    echo "Installing qpdf..."
    sudo apt-get install -y qpdf
}

# Function to install tools on CentOS/RHEL
install_centos() {
    echo "📦 Installing PDF tools on CentOS/RHEL..."
    
    # Install EPEL repository
    sudo yum install -y epel-release
    
    # Install poppler-utils
    echo "Installing poppler-utils..."
    sudo yum install -y poppler-utils
    
    # Install pdftk
    echo "Installing pdftk..."
    if sudo yum install -y pdftk 2>/dev/null; then
        echo "✅ pdftk installed successfully"
    else
        echo "❌ Could not install pdftk from standard repos"
    fi
    
    # Install qpdf
    echo "Installing qpdf..."
    sudo yum install -y qpdf
}

# Function to install tools on Fedora
install_fedora() {
    echo "📦 Installing PDF tools on Fedora..."
    
    # Install poppler-utils
    echo "Installing poppler-utils..."
    sudo dnf install -y poppler-utils
    
    # Install pdftk
    echo "Installing pdftk..."
    if sudo dnf install -y pdftk 2>/dev/null; then
        echo "✅ pdftk installed successfully"
    else
        echo "❌ Could not install pdftk from standard repos"
    fi
    
    # Install qpdf
    echo "Installing qpdf..."
    sudo dnf install -y qpdf
}

# Function to install tools on macOS
install_macos() {
    echo "📦 Installing PDF tools on macOS..."
    
    # Check if Homebrew is installed
    if ! command -v brew &> /dev/null; then
        echo "❌ Homebrew not found. Please install Homebrew first:"
        echo "   /bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\""
        exit 1
    fi
    
    # Install poppler (includes pdftotext)
    echo "Installing poppler..."
    brew install poppler
    
    # Install pdftk
    echo "Installing pdftk..."
    if brew install pdftk-java 2>/dev/null; then
        echo "✅ pdftk-java installed successfully"
    else
        echo "❌ Could not install pdftk"
    fi
    
    # Install qpdf
    echo "Installing qpdf..."
    brew install qpdf
}

# Function to test installations
test_tools() {
    echo ""
    echo "🧪 Testing installed tools..."
    echo "=========================="
    
    # Test pdftotext
    if command -v pdftotext &> /dev/null; then
        echo "✅ pdftotext: $(pdftotext -v 2>&1 | head -1)"
    else
        echo "❌ pdftotext: Not found"
    fi
    
    # Test pdftk
    if command -v pdftk &> /dev/null; then
        echo "✅ pdftk: $(pdftk --version 2>&1 | head -1)"
    else
        echo "❌ pdftk: Not found"
    fi
    
    # Test qpdf
    if command -v qpdf &> /dev/null; then
        echo "✅ qpdf: $(qpdf --version 2>&1 | head -1)"
    else
        echo "❌ qpdf: Not found"
    fi
    
    echo ""
}

# Function to show usage instructions
show_usage() {
    echo "📖 Usage Instructions"
    echo "==================="
    echo ""
    echo "1. Command Line Usage:"
    echo "   php pdf-form-extractor.php"
    echo ""
    echo "2. Web Interface:"
    echo "   Open pdf-extractor-web.php in your browser"
    echo ""
    echo "3. Place your PDF file in the same directory"
    echo ""
    echo "The extractor will:"
    echo "• Find all form fields (inputs, checkboxes, radio buttons)"
    echo "• Convert labels to lowercase with underscores"
    echo "• Generate JSON with processed keys"
    echo "• Save output to 'pdf-form-fields.json'"
    echo ""
}

# Main execution
main() {
    check_root
    detect_os
    
    echo "Detected OS: $OS"
    echo ""
    
    case $OS in
        "ubuntu")
            install_ubuntu
            ;;
        "centos")
            install_centos
            ;;
        "fedora")
            install_fedora
            ;;
        "macos")
            install_macos
            ;;
        *)
            echo "❌ Unsupported OS: $OS"
            echo "Please install the following tools manually:"
            echo "• poppler-utils (for pdftotext)"
            echo "• pdftk or pdftk-java"
            echo "• qpdf"
            exit 1
            ;;
    esac
    
    test_tools
    show_usage
    
    echo "🎉 Installation complete!"
    echo ""
    echo "You can now use the PDF Form Field Extractor."
    echo "Place your PDF file in this directory and run:"
    echo "  php pdf-form-extractor.php"
    echo ""
    echo "Or open pdf-extractor-web.php in your browser for the web interface."
}

# Run main function
main