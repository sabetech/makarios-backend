@extends('base')
@section('content')
<h1 class="md-typescale-display-medium">Hello! {{ $user->name }}</h1>
<img src="{{ $user->img_url }}" alt="Profile Picture" class="profile-picture"  style="width: 200px; height: auto;">
<p class="md-typescale-body-medium">Update Saved Successfully</p>
<p class="md-typescale-body-medium">Church Name:{{ $church }}</p>
<p class="md-typescale-body-medium">Stream Name:{{ $stream ?? "N/A"}}</p>
<p class="md-typescale-body-medium">Region Name:{{ $region ?? "N/A"}}</p>
<p class="md-typescale-body-medium">Zone Name:{{ $zone ?? "N/A" }}</p>
<p class="md-typescale-body-medium">Bacenta Name:{{ $bacenta ?? "N/A" }}</p>

<a href="{{ route('update_user_church_info') }}">
    Update another user
</a>

@endsection
