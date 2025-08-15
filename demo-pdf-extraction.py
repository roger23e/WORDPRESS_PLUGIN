#!/usr/bin/env python3
"""
PDF Form Field Extractor - Demonstration
This script simulates the PDF form field extraction process
and shows the expected JSON output for different types of form elements.
"""

import json
import re
from typing import Dict, List, Any

def process_label(label: str) -> str:
    """
    Process label to create JSON key
    - Convert to lowercase
    - Replace spaces with underscores
    - Remove special characters except underscores
    """
    # Convert to lowercase
    label = label.lower().strip()
    
    # Replace spaces with underscores
    label = re.sub(r'\s+', '_', label)
    
    # Remove special characters except underscores and alphanumeric
    label = re.sub(r'[^a-z0-9_]', '', label)
    
    # Remove multiple consecutive underscores
    label = re.sub(r'_+', '_', label)
    
    # Remove leading/trailing underscores
    label = label.strip('_')
    
    return label

def simulate_pdf_extraction() -> Dict[str, Any]:
    """
    Simulate PDF form field extraction for demonstration
    This represents what would be extracted from a typical PDF form
    """
    
    # Simulated form fields that would be found in a PDF
    simulated_fields = [
        {"type": "input", "label": "First Name"},
        {"type": "input", "label": "Last Name"},
        {"type": "input", "label": "Email Address"},
        {"type": "input", "label": "Phone Number"},
        {"type": "input", "label": "Date of Birth"},
        {"type": "input", "label": "Street Address"},
        {"type": "input", "label": "City"},
        {"type": "input", "label": "ZIP Code"},
        {"type": "input", "label": "Company Name"},
        {"type": "input", "label": "Job Title"},
        
        # Radio button group - Gender
        {"type": "radio", "label": "Male"},
        {"type": "radio", "label": "Female"},
        {"type": "radio", "label": "Other"},
        
        # Checkbox options - Interests
        {"type": "checkbox", "label": "Sports"},
        {"type": "checkbox", "label": "Music"},
        {"type": "checkbox", "label": "Technology"},
        {"type": "checkbox", "label": "Travel"},
        {"type": "checkbox", "label": "Reading"},
        {"type": "checkbox", "label": "Cooking"},
        
        # Checkbox options - Subscription preferences
        {"type": "checkbox", "label": "Newsletter Subscription"},
        {"type": "checkbox", "label": "Product Updates"},
        {"type": "checkbox", "label": "Event Notifications"},
        {"type": "checkbox", "label": "Marketing Communications"},
        
        # More input fields
        {"type": "input", "label": "Emergency Contact"},
        {"type": "input", "label": "Additional Comments"},
        
        # Complex labels to test processing
        {"type": "input", "label": "Social Security Number"},
        {"type": "checkbox", "label": "I agree to Terms & Conditions"},
        {"type": "checkbox", "label": "Subscribe to our weekly newsletter"},
        {"type": "radio", "label": "Preferred Contact Method: Email"},
        {"type": "radio", "label": "Preferred Contact Method: Phone"},
        {"type": "radio", "label": "Preferred Contact Method: Mail"},
    ]
    
    return simulated_fields

def generate_json(fields: List[Dict[str, str]]) -> Dict[str, Dict[str, str]]:
    """Generate JSON output with processed labels as keys"""
    json_output = {}
    
    for field in fields:
        label = field.get('label', '')
        field_type = field.get('type', 'unknown')
        
        # Process label to create key
        key = process_label(label)
        
        if key:  # Only add if key is not empty
            json_output[key] = {
                'type': field_type,
                'label': label,
                'value': ''  # Empty value for form filling
            }
    
    return json_output

def main():
    print("🔍 PDF Form Field Extractor - Demonstration")
    print("=" * 50)
    print()
    
    print("📄 Simulating PDF form field extraction...")
    print("This shows what would be extracted from a typical PDF form.")
    print()
    
    # Simulate extraction
    fields = simulate_pdf_extraction()
    
    print(f"✅ Found {len(fields)} form fields")
    print()
    
    # Generate JSON
    json_output = generate_json(fields)
    
    # Display field summary
    print("📋 Extracted Fields Summary:")
    print("-" * 30)
    
    field_counts = {}
    for field in fields:
        field_type = field['type']
        field_counts[field_type] = field_counts.get(field_type, 0) + 1
    
    for field_type, count in field_counts.items():
        print(f"  {field_type.upper()}: {count} fields")
    
    print()
    
    # Show label processing examples
    print("🔧 Label Processing Examples:")
    print("-" * 30)
    examples = [
        "First Name",
        "Email Address", 
        "Social Security Number",
        "I agree to Terms & Conditions",
        "Preferred Contact Method: Email"
    ]
    
    for example in examples:
        processed = process_label(example)
        print(f"  '{example}' → '{processed}'")
    
    print()
    
    # Generate and save JSON
    json_str = json.dumps(json_output, indent=2, ensure_ascii=False)
    
    # Save to file
    with open('/workspace/demo-extracted-fields.json', 'w', encoding='utf-8') as f:
        f.write(json_str)
    
    print("💾 JSON Output saved to: demo-extracted-fields.json")
    print()
    
    # Display JSON preview (first 10 fields)
    print("📝 JSON Preview (first 10 fields):")
    print("-" * 40)
    
    preview_items = list(json_output.items())[:10]
    preview_dict = dict(preview_items)
    preview_json = json.dumps(preview_dict, indent=2, ensure_ascii=False)
    print(preview_json)
    
    if len(json_output) > 10:
        print(f"\n... and {len(json_output) - 10} more fields")
    
    print()
    print("🎉 Demonstration complete!")
    print(f"📊 Total fields processed: {len(json_output)}")
    print("📁 Full JSON saved to demo-extracted-fields.json")
    
    return json_output

if __name__ == "__main__":
    main()