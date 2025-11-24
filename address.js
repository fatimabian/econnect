// DOM elements
const regionSelect = document.getElementById('regionSelect');
const provinceSelect = document.getElementById('provinceSelect');
const municipalitySelect = document.getElementById('municipalitySelect');
const barangaySelect = document.getElementById('barangaySelect');


let addressData = {};

// Load JSON file
fetch('data/philippine_provinces_cities_municipalities_and_barangays_2019v2.json')
  .then(res => res.json())
  .then(data => {
    addressData = data;
    populateRegions();
  })
  .catch(err => console.error('Error loading JSON:', err));

// Populate regions dropdown
function populateRegions() {
  regionSelect.innerHTML = '<option value="">Select Region</option>';
  Object.keys(addressData).forEach(regionCode => {
    const region = addressData[regionCode];
    regionSelect.innerHTML += `<option value="${regionCode}">${region.region_name}</option>`;
  });
}

// When region changes
regionSelect.addEventListener('change', () => {
  const regionCode = regionSelect.value;
  provinceSelect.innerHTML = '<option value="">Select Province</option>';
  municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
  barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

  if (!regionCode) return;

  const provinces = addressData[regionCode].province_list;
  Object.keys(provinces).forEach(provinceName => {
    provinceSelect.innerHTML += `<option value="${provinceName}">${provinceName}</option>`;
  });
});

// When province changes
provinceSelect.addEventListener('change', () => {
  const regionCode = regionSelect.value;
  const provinceName = provinceSelect.value;

  municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
  barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

  if (!provinceName) return;

  const municipalities = addressData[regionCode].province_list[provinceName].municipality_list;
  Object.keys(municipalities).forEach(municipalityName => {
    municipalitySelect.innerHTML += `<option value="${municipalityName}">${municipalityName}</option>`;
  });
});

// When municipality changes
municipalitySelect.addEventListener('change', () => {
  const regionCode = regionSelect.value;
  const provinceName = provinceSelect.value;
  const municipalityName = municipalitySelect.value;

  barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

  if (!municipalityName) return;

  const barangays = addressData[regionCode].province_list[provinceName].municipality_list[municipalityName].barangay_list;
  barangays.forEach(brgy => {
    barangaySelect.innerHTML += `<option value="${brgy}">${brgy}</option>`;
  });
});
