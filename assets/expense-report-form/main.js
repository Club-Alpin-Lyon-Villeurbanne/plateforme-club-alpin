import ExpenseReportForm from "./src/ExpenseReportForm.vue";
import { createApp } from "vue";

// External dependency loaded by the Symfony application
const app = createApp(ExpenseReportForm);
app.provide("toastr", toastr);

app.mount("#expense-report-form");
