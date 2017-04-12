@extends ('layout.master')

@section ('pageTitle')
Doorstuuradressen
@endsection

@section ('content')
<table>
	<thead>
		<tr>
			<th></th>
			<th>E-mailadres</th>
			<th>Bestemming</th>
		</tr>
	</thead>
	<tbody>
		@foreach ($mFwds as $mFwd)
		<tr>
			<td>
				<div class="button-group radius">
					@if($mFwd->uid === $mFwd->mailDomainVirtual->uid)
					<a href="/mail/forwarding/{{ $mFwd->id }}/edit" title="Bewerken" class="button tiny">
						<img src="/img/icons/edit.png" alt="Bewerken" />
					</a><a href="/mail/forwarding/{{ $mFwd->id }}/remove" title="Verwijderen" class="button tiny alert remove">
						<img src="/img/icons/remove.png" alt="Verwijderen" />
					</a>
					@endif
				</div>
			</td>
			<td>
				@if ($mFwd->mailDomainVirtual)
					{{$mFwd->source . '@' . $mFwd->mailDomainVirtual->domain}}
				@else
					{{ $mFwd->source }}
				@endif
			</td>
			<td>{{ $mFwd->destination }}</td>
		</tr>
		@endforeach
	</tbody>
</table>
<div class="right">
	<a href="/mail/forwarding/create" title="Toevoegen" class="button radius">
		<img src="/img/icons/add.png" alt="Toevoegen" />
	</a>
</div>
@endsection