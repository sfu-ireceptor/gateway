<div class="form-group">
	{{ Form::label($field['ir_id'], __('short.' . $field['ir_id'])) }}
	@include('help', ['id' => $field['ir_id']])
	{{ Form::text($field['ir_id'], '', array('class' => 'form-control')) }}
</div>
