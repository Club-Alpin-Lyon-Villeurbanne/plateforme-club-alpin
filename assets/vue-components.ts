import { createApp } from "vue";
import ExpenseReportFormV2 from "./expense-report-form/src/ExpenseReportFormV2.vue";

(window as any).vue = {
  createApp,
  // register your component here
  components: {
    ExpenseReportFormV2,
  },
};
