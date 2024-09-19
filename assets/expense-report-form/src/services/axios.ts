import axios from "axios";

const apiBaseUrl = ((window as any).globals.apiBaseUrl ?? "/") + "/api";

axios.defaults.baseURL = apiBaseUrl;
axios.defaults.headers.common["Content-Type"] = "application/json";
axios.defaults.headers.common["Accept"] = "application/json";
axios.defaults.headers.common["Authorization"] = `Bearer ${localStorage.getItem(
  "jwt"
)}`;

export default axios;
