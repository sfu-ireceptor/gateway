@if(__('desc.' . $id) != '')
	<a role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" title="{{ __('short.' . $id) }}" data-content="<p>{{ __('desc.' . $id) }}</p>@if(__('ex.' . $id) != '') Example: <em>{{ __('ex.' . $id) }}</em>@endif" data-trigger="hover" tabindex="0">
		<span class="glyphicon glyphicon-question-sign"></span>
	</a>
@endif

