<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
        <script type="importmap">
          {
            "imports": {
              "@material/web/": "https://esm.run/@material/web/"
            }
          }
        </script>
        <script type="module">
          import '@material/web/all.js';
          import {styles as typescaleStyles} from '@material/web/typography/md-typescale-styles.js';

          document.adoptedStyleSheets.push(typescaleStyles.styleSheet);
        </script>
        <style>
            /* Global styles */
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: Arial, sans-serif;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 16px;
                background-color: #fff7eb;
            }

            .container {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 90%;
                max-width: 500px;
                background: white;
                padding: 20px;
                border-radius: 16px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                text-align: center;
            }

            h1 {
                font-size: 1.5rem;
                margin-bottom: 8px;
            }

            form {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 100%;
                gap: 16px;
            }

            md-outlined-text-field {
                width: 100%;
                max-width: 300px;
            }

            .autocomplete-container {
                position: relative;
                width: 100%;
                max-width: 300px;
            }

            .autocomplete-list {
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ccc;
                border-radius: 4px;
                max-height: 200px;
                overflow-y: auto;
                display: none;
                z-index: 1000;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            }

            .autocomplete-item {
                padding: 10px;
                font-size: 14px;
                cursor: pointer;
            }

            .autocomplete-item:hover {
                background: #f0f0f0;
            }

            md-outlined-button {
                width: 100%;
                max-width: 300px;
            }

            /* Responsive adjustments */
            @media (max-width: 480px) {
                h1 {
                    font-size: 1.2rem;
                }

                .container {
                    width: 95%;
                    padding: 15px;
                }

                md-outlined-text-field,
                .autocomplete-container,
                md-outlined-button {
                    max-width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="md-typescale-display-medium">Hello!</h1>
            <form>
                <p class="md-typescale-body-medium">Update your church information</p>
                <div class="autocomplete-container">
                    <md-outlined-text-field id="autocomplete-input" label="Search for your name"></md-outlined-text-field>
                    <div class="autocomplete-list" id="autocomplete-list"></div>
                </div>
                <md-outlined-button type="reset">Select</md-outlined-button>
            </form>
        </div>
        <style>
            form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
            }
        </style>

        <script>
            // Debug: Check if Laravel users are passed correctly
            const users = @json($users);
            console.log("Users from Laravel:", users);

            const input = document.getElementById("autocomplete-input");
            const list = document.getElementById("autocomplete-list");

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

    </body>
</html>
