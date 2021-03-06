@extends ('layout.master')

@section ('pageTitle')
E-maildomeinen &bull; Staff
@endsection

@section ('content')
{{ $domains->links () }}
<table>
	<thead>
		<tr>
			<th></th>
			<th>Domein</th>
			<th>Gebruiker</th>
		</tr>
	</thead>
	<tbody>
		@foreach ($domains as $domain)
		<tr>
			<td>
				<div class="button-group radius">
					<a href="/staff/mail/domain/{{ $domain->id }}/edit" title="Bewerken" class="button tiny">
						<img src="/img/icons/edit.png" alt="Bewerken" />
					</a><a href="/staff/mail/domain/{{ $domain->id }}/remove" title="Verwijderen" class="button tiny alert remove">
						<img src="/img/icons/remove.png" alt="Verwijderen" />
					</a>
				</div>
			</td>
			<td>
				@if ($domain->user->hasExpired ())
					<img src="/img/icons/vhost-expired.png" alt="[Expired]" />
				@endif
				{{ $domain->domain }}
			</td>
			<td>
				<span class="{{ $domain->user->gid < Group::where ('name', 'user')->firstOrFail ()->gid ? 'label' : '' }}">{{ $domain->user->userInfo->username }}</span>
			</td>
		</tr>
		@endforeach
	</tbody>
</table>
{{ $domains->links () }}
<div class="right">
	<a href="/staff/mail/domain/create" title="Toevoegen" class="button radius">
		<img src="/img/icons/add.png" alt="Toevoegen" />
	</a>
</div>

@include ('staff.mail.search_part')
@endsection