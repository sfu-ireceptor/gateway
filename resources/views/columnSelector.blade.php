<div class="collapse" id="column_selector">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 class="panel-title">
				Customize displayed columns
				<button class="btn btn-primary btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector">
					<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
		  			Close
				</button>
			</h4>
		</div>
  		<div class="panel-body">
			<form class="column_selector">
				@foreach ($field_list_grouped as $field_group_id => $field_group)
					<div class="column_group">
						<h5>
							<input type="checkbox" name="table_columns_group" value="{{ $field_group_id }}" />
							{{ $field_group['name'] }}
						</h5>
						@foreach ($field_group['fields'] as $field)
							<div class="checkbox">
								<label>
									<input type="checkbox" name="table_columns" class="{{ $field['ir_id'] }}" data-id="{{ $field['ir_id'] }}" value="{{'col_' . $field['ir_id']}}" {{ in_array($field['ir_id'], $current_columns) ? 'checked="checked"' : '' }}/>
									 @include('help', ['id' => $field['ir_id']])
									 @lang('short.' . $field['ir_id'])
								</label>
							</div>		
						@endforeach
					</div>
				@endforeach
			</form>
  		</div>
	</div>
</div>
