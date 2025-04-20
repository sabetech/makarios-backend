@extends('base')
@section('content')
    <p>
        Info! <br><br>

        Step 2: Change any information here that is not right to the correct one. <br><br>
    </p>
    <h1 class="md-typescale-display-medium">Hello! {{ $user->name }}</h1>
    <img src="{{ $user->img_url }}" alt="Profile Picture" class="profile-picture"  style="width: 200px; height: auto;">
    <form method="POST" action="{{ route('update_user', $user->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <p class="md-typescale-body-medium">Update your Bio Data</p>
        <md-outlined-text-field name="name" label="Name" value="{{ $user->name }}"></md-outlined-text-field>
        <md-outlined-text-field name="email" label="Email" value="{{ $user->email }}"></md-outlined-text-field>
        <md-outlined-text-field name="phone" label="Phone" value="{{ $user->phone }}"></md-outlined-text-field>
        <md-outlined-text-field name="address" label="Home Address" value="{{ $user->home_address }}"></md-outlined-text-field>


        <input type="file" name="profile_picture" id="fileInput" >
        <p class="file-name" id="fileName">Upload a new picture</p>
        {{-- <md-filled-tonal-button id="uploadButton">Choose File</md-filled-tonal-button> --}}
        {{-- <button id="uploadButton">Choose File</button>
        <p class="file-name" id="fileName">No file selected</p> --}}

        <hr />

        <p class="md-typescale-body-medium">Update your church information</p>
        <div class="autocomplete-container">
            <md-outlined-text-field id="autocomplete-stream" label="Choose your Stream" value="{{$user->stream->name ?? ''}}"></md-outlined-text-field>
            <div class="autocomplete-list" id="autocomplete-list-stream"></div>
            <input name="stream" id="stream-id" type="hidden" value="{{$user->stream->id ?? ''}}">
        </div>

        <div class="autocomplete-container">
            <md-outlined-text-field id="autocomplete-region" label="Choose your Region" value="{{$user->region->name ?? ''}}"></md-outlined-text-field>
            <div class="autocomplete-list" id="autocomplete-list-region"></div>
            <input name="region" id="region-id" type="hidden" value="{{$user->region->id ?? ''}}">
        </div>

        <div class="autocomplete-container">
            <md-outlined-text-field id="autocomplete-zone" label="Choose your Zone" value="{{$user->zone->name ?? ''}}"></md-outlined-text-field>
            <div class="autocomplete-list" id="autocomplete-list-zone"></div>
            <input name="zone" id="zone-id" type="hidden" value="{{$user->zone->id ?? ''}}">
        </div>

        <div class="autocomplete-container">
            <md-outlined-text-field id="autocomplete-bacenta" label="Choose your bacenta" value="{{$user->bacenta->name ?? ''}}"></md-outlined-text-field>
            <div class="autocomplete-list" id="autocomplete-list-bacenta"></div>
            <input name="bacenta" id="bacenta-id" type="hidden" value="{{$user->bacenta->id ?? ''}}">
        </div>

        <md-outlined-text-field name="church_name" label="Church Name" value="Word of Life Cathedral" readonly></md-outlined-text-field>

        <md-filled-tonal-button type="submit" style="width:50%;float: right">Update</md-filled-tonal-button>
@endsection

@section('scripts')
<script>
    const fileInput = document.getElementById("fileInput");
    const uploadButton = document.getElementById("uploadButton");
    const fileNameDisplay = document.getElementById("fileName");

    const streams = @json($streams);
    const regions = @json($regions);
    const zones = @json($zones);
    const bacentas = @json($bacentas);

    const autocompleteStream = document.getElementById("autocomplete-stream");
    const autocompleteRegion = document.getElementById("autocomplete-region");
    const autocompleteZone = document.getElementById("autocomplete-zone");
    const autocompleteBacenta = document.getElementById("autocomplete-bacenta");

    const streamId = document.getElementById("stream-id");
    const regionId = document.getElementById("region-id");
    const zoneId = document.getElementById("zone-id");
    const bacentaId = document.getElementById("bacenta-id");

    // Autocomplete for Stream
    autocompleteStream.addEventListener("input", () => {
        const value = autocompleteStream.value.toLowerCase().trim();
        const filtered = streams.filter(stream => stream.name.toLowerCase().includes(value));
        const list = document.getElementById("autocomplete-list-stream");
        list.innerHTML = ""; // Clear previous suggestions

        if (filtered.length === 0) {
            list.style.display = "none";
            return;
        }

        if (value === "") {
            list.style.display = "none";
            streamId.value = ""
            return;
        }

        filtered.forEach(stream => {
            const item = document.createElement("div");
            item.classList.add("autocomplete-item");
            item.textContent = stream.name;

            item.addEventListener("click", () => {
                autocompleteStream.value = stream.name;
                streamId.value = stream.id;
                list.style.display = "none";
            });

            list.appendChild(item);

        });

        list.style.display = "block";
    })

    autocompleteRegion.addEventListener("input", () => {
        const value = autocompleteRegion.value.toLowerCase().trim();
        const filtered = regions.filter(region => region.name.toLowerCase().includes(value));
        const list = document.getElementById("autocomplete-list-region");
        list.innerHTML = ""; // Clear previous suggestions
        if (filtered.length === 0) {
            list.style.display = "none";
            return;
        }

        if (value === "") {
            list.style.display = "none";
            regionId.value = "";
            return;
        }

        filtered.forEach(region => {
            const item = document.createElement("div");
            item.classList.add("autocomplete-item");
            item.textContent = region.name;

            item.addEventListener("click", () => {
                autocompleteRegion.value = region.name;
                region.value = region.id;
                list.style.display = "none";
            });

            list.appendChild(item);
        });
        list.style.display = "block";
    })


    autocompleteZone.addEventListener("input", () => {
        const value = autocompleteZone.value.toLowerCase().trim();
        const filtered = zones.filter(zone => zone.name.toLowerCase().includes(value));
        const list = document.getElementById("autocomplete-list-zone");
        list.innerHTML = ""; // Clear previous suggestions
        if (filtered.length === 0) {
            list.style.display = "none";
            return;
        }

        if (value === "") {
            list.style.display = "none";
            zoneId.value = "";
            return;
        }

        filtered.forEach(zone => {
            const item = document.createElement("div");
            item.classList.add("autocomplete-item");
            item.textContent = zone.name;
            item.addEventListener("click", () => {
                autocompleteZone.value = zone.name;
                zoneId.value = zone.id;
                list.style.display = "none";
            });
            list.appendChild(item);
        });

        list.style.display = "block";
    })

    // Autocomplete for Bacenta
    autocompleteBacenta.addEventListener("input", () => {
        const value = autocompleteBacenta.value.toLowerCase().trim();
        const filtered = bacentas.filter(bacenta => bacenta.name.toLowerCase().includes(value));
        const list = document.getElementById("autocomplete-list-bacenta");
        list.innerHTML = ""; // Clear previous suggestions
        if (filtered.length === 0) {
            list.style.display = "none";
            return;
        }

        if (value === "") {
            list.style.display = "none";
            bacentaId.value = "";
            return;
        }

        filtered.forEach(bacenta => {
            const item = document.createElement("div");
            item.classList.add("autocomplete-item");
            item.textContent = bacenta.name + " (" + bacenta.region.name + ")";
            item.addEventListener("click", () => {
                autocompleteBacenta.value = bacenta.name + " (" + bacenta.region.name + ")";
                bacentaId.value = bacenta.id;
                list.style.display = "none";
            });
            list.appendChild(item);
        })
        list.style.display = "block";
    })

    uploadButton.addEventListener("click", () => {
        fileInput.click();
    });




    // Update file name when a file is selected
    fileInput.addEventListener("change", () => {
        if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = fileInput.files[0].name;
        } else {
            fileNameDisplay.textContent = "No file selected";
        }
    });

</script>
@endsection
