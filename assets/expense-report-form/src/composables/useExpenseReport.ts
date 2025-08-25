import { inject, InjectionKey, onMounted, provide, Ref, ref } from "vue";
import {
  ExpenseDetails,
  ExpenseReport,
  ExpenseReportPayload,
} from "../types/api";
import axios from "../services/axios";
import { useAttachments } from "./useAttachment";
import config from "../config/expense-reports.json";

export interface ExpenseReportContext {
  expenseReport: Ref<ExpenseReport | null>;
  isLoading: Ref<boolean>;
  saveAsDraft: (payload: Partial<ExpenseReport>) => Promise<void>;
  submit: (payload: Partial<ExpenseReport>) => Promise<void>;
}

export const ExpenseReportKey: InjectionKey<ExpenseReportContext> =
  Symbol("ExpenseReport");

export function useExpenseReport(initialEventId: number) {
  const expenseReport = ref<ExpenseReport | null>(null);
  const toastr: Toastr | undefined = inject("toastr");
  const isLoading = ref(true);

  const { setAttachments } = useAttachments();

  const save = async (payload: Partial<ExpenseReport>) => {
    let body: ExpenseReportPayload = {
      details: JSON.stringify(payload.details),
      refundRequired: payload.refundRequired ?? true,
    };
  
    if (payload.status) {
      body = { ...body, status: payload.status };
    }
  
    await axios.patch<ExpenseReport>(
      `${config.endpoints.notesDeFrais}/${expenseReport.value?.id}`,
      body,
    );
  };

  const saveAsDraft = async (payload: Partial<ExpenseReport>) => {
    try {
      await save(payload);
      toastr?.info("Votre note de frais a été sauvegardée");
    } catch (error) {
      toastr?.error("Une erreur s'est produite");
    }
  };

  const submit = async (payload: Partial<ExpenseReport>) => {
    try {
      await save({ ...payload, status: "submitted" });
      await fetchOrCreateExpenseReport(initialEventId);
      toastr?.success("Votre note de frais a bien été envoyée");
    } catch (error) {
      toastr?.error("Une erreur s'est produite");
    }
  };

  const initializeDetails = (details: any): ExpenseDetails => {
    return {
      transport: details.transport || {},
      accommodations: details.accommodations || [],
      others: details.others || [],
    };
  };

  const fetchOrCreateExpenseReport = async (eventId: number) => {
    try {
      const response = await axios.get<{ data: ExpenseReport[], meta: any }>(
        `${config.endpoints.notesDeFrais}?inclure_brouillons=true&event=${eventId}`,
      );

      let fetchedReport: ExpenseReport;

      if (response.data.data.length > 0) {
        fetchedReport = response.data.data[0];
      } else {
        const createResponse = await axios.post<ExpenseReport>(
          config.endpoints.notesDeFrais,
          { sortie: `/api/sorties/${eventId}` },
        );
        fetchedReport = createResponse.data;
      }

      let parsedDetails: ExpenseDetails;
      fetchedReport.details = fetchedReport.details || {};

      if (typeof fetchedReport.details === "string") {
        parsedDetails = initializeDetails(JSON.parse(fetchedReport.details));
      } else {
        parsedDetails = initializeDetails(fetchedReport.details);
      }

      expenseReport.value = {
        ...fetchedReport,
        details: parsedDetails,
      };
      setAttachments(expenseReport.value.piecesJointes);
    } catch (error) {
      console.error(
        "Erreur lors de la récupération/création de la note de frais:",
        error,
      );
      toastr?.error("Une erreur s'est produite");
    } finally {
      isLoading.value = false;
    }
  };

  onMounted(async () => {
    if (initialEventId) {
      await fetchOrCreateExpenseReport(initialEventId);
    }
  });

  const context: ExpenseReportContext = {
    expenseReport,
    isLoading,
    saveAsDraft,
    submit,
  };

  provide(ExpenseReportKey, context);

  return context;
}
