import { createApp } from "vue";

import ExpenseReportFormV2 from "./src/ExpenseReportFormV2.vue";

const app = createApp(ExpenseReportFormV2);

// External dependency loaded by the Symfony application
app.provide("toastr", toastr);

app.mount("#expense-report-form");
