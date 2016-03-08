# Playa & Matrix Importer for ExpressionEngine 2

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
