import * as zod from "zod";
import { TransportType } from "../types/transports";

export const schema = zod.object({
  transport: zod.discriminatedUnion("type", [
    zod.object({
      type: zod.literal(TransportType.RENTAL_MINIBUS),
      tollFee: zod.number().min(0).optional(),
      fuelExpense: zod.number().min(0),
      rentalPrice: zod.number().min(0),
      passengerCount: zod.number().min(1),
    }),
    zod.object({
      type: zod.literal(TransportType.PUBLIC_TRANSPORT),
      ticketPrice: zod.number().min(0),
    }),
    zod.object({
      type: zod.literal(TransportType.PERSONAL_VEHICLE),
      tollFee: zod.number().min(0).optional(),
      distance: zod.number().min(1),
    }),
    zod.object({
      type: zod.literal(TransportType.CLUB_MINIBUS),
      tollFee: zod.number().min(0).optional(),
      fuelExpense: zod.number().min(0),
      distance: zod.number().min(1),
      passengerCount: zod.number().min(1),
    }),
  ]),
  accommodations: zod
    .array(
      zod.object({
        expenseId: zod.string(),
        price: zod.number().min(1),
        comment: zod.string(),
      }),
    )
    .default([]),
  others: zod
    .array(
      zod.object({
        expenseId: zod.string(),
        price: zod.number().min(1),
        comment: zod.string(),
      }),
    )
    .default([]),
  refundRequired: zod.boolean(),
});
