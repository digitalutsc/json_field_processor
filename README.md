# JSON Field Processor

**JSON Field Processor** is a Drupal module that allows you to index JSON data from nodes or media entities into a Search API index.

---

## Installation

1. Install the module via Composer:
   ```bash
   composer require drupal/json_field_processor
   ```
2. Ensure the following dependencies are installed and enabled:
   - **Search API**
   - **JSON Field**

---

## Usage

1. Enable the **JSON Field Processor** module.
2. Configure the processor for your Search API index:
   - Go to your index settings and enable the **JSON Field Processor**.
3. Navigate to the module's configuration page (under Search and metadata - near search api configuration page) to add fields for indexing:
   - **Field Name**: The name of the field as it will appear in your index. This is the machine name and must be unique.
   - **JSON Path**: The JSON path to the specific data you wish to index.
   - **JSON Field Machine Name**: The machine name of the JSON field where the module will look for the JSON path.
4. Add the configured field to your index.

If you delete a configuration entity, it will automatically delete the field from your index(s).
