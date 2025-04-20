@extends('base')
@section('content')
<h1 class="md-typescale-display-medium">Hello!</h1>
<p class="md-typescale-body-medium">
    Info! <br><br>

    This is to help update the church information of leaders. <br><br>
    Step 1: Search for Leader and Tap to select the search suggestions <br><br>
</p>

<form method="POST" action="{{ route('getUserInfo') }}">
    @csrf

    @if (isset($success))
        <p class="md-typescale-body-medium">{{ $success}}</p>
    @endif
    <div class="autocomplete-container">
        <md-outlined-text-field id="autocomplete-input" label="Search for your name"></md-outlined-text-field>
        <div class="autocomplete-list" id="autocomplete-list"></div>
        <input name="id" id="user-id" type="hidden" value="">
    </div>
    <md-outlined-button type="submit">Select</md-outlined-button>
</form>
@endsection

@section('scripts')
<script>
    // Debug: Check if Laravel users are passed correctly
    const users = @json($users);
    console.log("Users from Laravel:", users);

    const input = document.getElementById("autocomplete-input");
    const list = document.getElementById("autocomplete-list");
    const userId = document.getElementById("user-id");

    input.addEventListener("input", function () {
        const value = this.value.toLowerCase().trim();
        list.innerHTML = ""; // Clear previous suggestions

        // Debug: Check input value
        console.log("User input:", value);

        if (!value) {
            list.style.display = "none";
            return;
        }

        // Filter users based on input
        const filtered = users.filter(user => user.name.toLowerCase().includes(value));

        // Debug: Check filtered results
        console.log("Filtered users:", filtered);

        if (filtered.length === 0) {
            list.style.display = "none";
            return;
        }

        filtered.forEach(user => {
            const item = document.createElement("div");
            item.classList.add("autocomplete-item");
            item.textContent = user.name;

            item.addEventListener("click", function () {
                input.value = user.name;
                list.style.display = "none";
                userId.value = user.id; // Set the hidden input value to the selected user's ID
            });

            list.appendChild(item);
        });

        list.style.display = "block";
    });

    // Hide dropdown when clicking outside
    document.addEventListener("click", function (e) {
        if (!input.contains(e.target) && !list.contains(e.target)) {
            list.style.display = "none";
        }
    });
</script>
@endsection
