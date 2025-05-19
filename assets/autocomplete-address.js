import "./styles/autocomplete-address.css";

const addressInput = document.getElementById("event_rdv");
const suggestionsBox = document.getElementById("suggestions");
const latInput = document.getElementById("event_lat");
const lonInput = document.getElementById("event_long");

let debounceTimeout;
addressInput.addEventListener("input", () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        fetchSuggestions(addressInput.value);
    }, 300);
});

async function fetchSuggestions(query) {
    if (query.length < 5) {
        suggestionsBox.innerHTML = "";
        suggestionsBox.style.display = "none";
        return;
    }

    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5&countrycodes=fr`);
    const results = await response.json();

    suggestionsBox.innerHTML = "";
    results.forEach(result => {
        console.log(result);

        if (["square", "city", "town", "house", "pedestrian", "residential"].includes(result.type)) {
            let address = (undefined !== result.address.house_number) ? result.address.house_number + ' ' : '';
            address += result.address.road + ' ' + result.address.postcode + ' ';
            address += (undefined !== result.address.town) ? result.address.town : result.address.city;

            const div = document.createElement("div");
            div.textContent = address;
            div.addEventListener("click", () => {
                addressInput.value = address;
                latInput.value = result.lat;
                lonInput.value = result.lon;
                suggestionsBox.innerHTML = "";
            });
            suggestionsBox.appendChild(div);
            suggestionsBox.style.display = "block";
        }
    });
}

// Fermer les suggestions si clic en dehors
document.addEventListener("click", (e) => {
    if (!suggestionsBox.contains(e.target) && e.target !== addressInput) {
        suggestionsBox.innerHTML = "";
        suggestionsBox.style.display = "none";
    }
});
