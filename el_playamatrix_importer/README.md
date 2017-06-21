# Playa & Matrix Importer for ExpressionEngine 2

[![Build Status](https://travis-ci.org/EllisLab/PlayaMatrixImporter.svg?branch=master)](https://travis-ci.org/EllisLab/PlayaMatrixImporter)

This is a module for importing existing Playa and Matrix fields into new Relationship and Grid fields. The import is *non-destructive*, meaning it doesn't convert or change the existing fields or data in any way. It creates _new_ fields and copies the data over to them, so you have a chance to inspect and check that the import has done its job before you commit to the new fields.

## Prerequisites

This importer was developed and tested in the context of at least:

* ExpressionEngine 2.10.2
* Matrix 2.6.1
* Playa 4.5.2

Best to make sure any third-party celltypes you have that are also compatible with Grid are up-to-date as well to ensure their Grid implementations are in place.

## Usage

1. Back up your database
2. Copy the el_playamatrix_importer folder to your system/expressionengine/third_party folder and install the module.
3. Navigate to the module's control panel page, at Addons -> Modules -> Playa & Matrix Importer.
4. Click the Import button.
5. Existing Playa and Matrix fields should now have native Relationship/Grid copies in their respective field groups.
6. Check some channel entries to make sure the data has been successfully mirrored.
7. Take note of the new field names and update your template tags to the proper syntax for [Relationships](https://docs.expressionengine.com/latest/fieldtypes/relationships.html#template-tags) and [Grid](https://docs.expressionengine.com/latest/fieldtypes/grid.html#template-tags).
8. You're free to delete or discontinue using the old Playa & Matrix fields.

## Matrix celltype mapping

Each celltype native to Matrix has been mapped to a native EE near-equivalent. If the celltype is also Grid-compatible, the same celltype will be used. The importer also attempts to read the field settings for each Matrix column and normalize them to a format readable by ExpressionEngine's native fieldtypes. Settings for third party fieldtypes are brought over without modification.

| Matrix celltype         | Mapped native fieldtype                      |
| ----------------------- | -------------------------------------------- |
| Date                    | Date                                         |
| Fieldpack Checkboxes    | Checkboxes                                   |
| Fieldpack Dropdown      | Select                                       |
| Fieldpack List          | Textarea                                     |
| Fieldpack Multiselect   | Multiselect                                  |
| Fieldpack Pill          | Radio                                        |
| Fieldpack Radio Buttons | Radio                                        |
| Fieldpack Switch        | Radio                                        |
| File                    | File                                         |
| Number                  | Text (with number content type)              |
| Playa                   | Relationship                                 |
| Rich Text               | Rich Text                                    |
| Text                    | Text for single line, Textarea for multiline |

## Gotchas

Matrix celltypes and native ExpressionEngine fieldtypes do not mirror functionality 1:1, so there are some issues to note when importing Matrix fields with certain celltypes.

* Fieldpack List essentially stored its data like a Textarea would, so we've opted to map that celltype to a Textarea. But, this will not get you the front-end tag-pair syntax that comes with Fieldpack List that allows you to loop through the list items.
* Fieldpack celltypes that deal with key/value options like Dropdown, Multiselect, Pill, Radio Buttons, and Switch will no longer have access to those keys in templates. Since ExpressionEngine's native equivalent fieldtypes only work with values, only the values will be preserved.

## Other third-party add-on notes

* If you have Publisher and Low Search installed, you'll need to regenerate Collections before editing entries.

## Problems?

If you run into anything, head on over to [the issues](https://github.com/EllisLab/PlayaMatrixImporter/issues) to see if a similar issue has been reported, or to submit a new one.

## Change Log

- 1.0.8 - June 21, 2017
    - Fixed a bug (#10) where the importer may error when encountering orphaned Playa or Assets data.
- 1.0.7 - May 30, 2017
    - Fixed a bug (#12) where dropdown and select cells with groups would not get imported correctly.
- 1.0.6 - January 19, 2017
    - Fixed a bug (#8) where the importer may not import data from the first Matrix field it encounters.
- 1.0.5 - December 16, 2016
    - Fixed a bug (#5) where the importer may show errors when encountering orphaned Playa data belonging to a nonexistent Matrix field.
- 1.0.4 - May 17, 2016
    - Fixed a bug where the importer may fail when importing a Matrix field that has no data.
- 1.0.3 - May 10, 2016
    - Fixed a bug (#3) where the importer may fail if the `var_id` column in the Matrix columns table wasn't consistently `NULL`.
- 1.0.2 - May 9, 2016
    - Fixed a bug (#2) where the importer may show an error if the `var_id` column was missing from the Matrix columns table.
- 1.0.1 - March 16, 2016
    - Fixed a bug (#1) where the importer would not work if either Playa or Matrix were uninstalled while the other was installed.
    - Fixed a bug (#1) where importing a Playa field that had no relationships would cause errors.

## Additional Files

You may be wondering what the rest of the files in this package are for. They are solely for development, so if you are forking the GitHub repo, they can be helpful. If you are just using the add-on in your ExpressionEngine installation, you can ignore all of these files.

- **.editorconfig**: [EditorConfig](http://editorconfig.org) helps developers maintain consistent coding styles across files and text editors.
- **.gitignore:** [.gitignore](https://git-scm.com/docs/gitignore) lets you specify files in your working environment that you do not want under source control.
- **.travis.yml:** A [Travis CI](https://travis-ci.org) configuration file for continuous integration (automated testing, releases, etc.).
- **.composer.json:** A [Composer project setup file](https://getcomposer.org/doc/01-basic-usage.md) that manages development dependencies.
- **.composer.lock:** A [list of dependency versions](https://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file) that Composer has locked to this project.

## License

Copyright (C) 2004 - 2016 EllisLab, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL ELLISLAB, INC. BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Except as contained in this notice, the name of EllisLab, Inc. shall not be used in advertising or otherwise to promote the sale, use or other dealings in this Software without prior written authorization from EllisLab, Inc.
