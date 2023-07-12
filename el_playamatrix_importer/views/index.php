<?=form_open($form_action);?>

<h3><?= lang('el_playamatrix_importer_module_description'); ?></h3>
<?= lang('el_playamatrix_importer_module_long_description'); ?>

<?= $message; ?>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    lang('id'),
    lang('field')
);

foreach ($fields as $field_id => $field_name)
{
	$this->table->add_row($field_id, '<label>'.form_checkbox('fields[]', $field_id).' '.$field_name.'</label>');
}

echo $this->table->generate();

?>

<p><?=form_submit('submit', lang('btn_import'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>
