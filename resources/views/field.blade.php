<div class="form-group">
	{{ Form::label($field['ir_id'], __('short.' . $field['ir_id'])) }}

	@include('help', ['id' => $field['ir_id']])

	<span class="remove_field" role="button" data-container="body" title="Remove filter">
		<span class="glyphicon glyphicon-remove"></span>
	</span>

	{{ Form::text($field['ir_id'], '', array('class' => 'form-control')) }}
</div>
