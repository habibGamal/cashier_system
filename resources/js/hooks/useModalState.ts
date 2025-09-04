import { useState } from "react";
import { IModalState, IModalActions } from "@/types/OrderManagement";

// Single Responsibility Principle - This hook only manages modal state
export const useModalState = (includeDriver = false, includeChangeOrderType = false) => {
    const [modalState, setModalState] = useState<IModalState>({
        isCustomerModalOpen: false,
        isDriverModalOpen: includeDriver ? false : undefined,
        isOrderNotesModalOpen: false,
        isOrderDiscountModalOpen: false,
        isPaymentModalOpen: false,
        isChangeOrderTypeModalOpen: includeChangeOrderType ? false : undefined,
    });

    const modalActions: IModalActions = {
        openCustomerModal: () => setModalState(prev => ({ ...prev, isCustomerModalOpen: true })),
        closeCustomerModal: () => setModalState(prev => ({ ...prev, isCustomerModalOpen: false })),

        openDriverModal: includeDriver ? () => setModalState(prev => ({ ...prev, isDriverModalOpen: true })) : undefined,
        closeDriverModal: includeDriver ? () => setModalState(prev => ({ ...prev, isDriverModalOpen: false })) : undefined,

        openOrderNotesModal: () => setModalState(prev => ({ ...prev, isOrderNotesModalOpen: true })),
        closeOrderNotesModal: () => setModalState(prev => ({ ...prev, isOrderNotesModalOpen: false })),

        openOrderDiscountModal: () => setModalState(prev => ({ ...prev, isOrderDiscountModalOpen: true })),
        closeOrderDiscountModal: () => setModalState(prev => ({ ...prev, isOrderDiscountModalOpen: false })),

        openPaymentModal: () => setModalState(prev => ({ ...prev, isPaymentModalOpen: true })),
        closePaymentModal: () => setModalState(prev => ({ ...prev, isPaymentModalOpen: false })),

        openChangeOrderTypeModal: includeChangeOrderType ? () => setModalState(prev => ({ ...prev, isChangeOrderTypeModalOpen: true })) : undefined,
        closeChangeOrderTypeModal: includeChangeOrderType ? () => setModalState(prev => ({ ...prev, isChangeOrderTypeModalOpen: false })) : undefined,
    };

    return {
        modalState,
        modalActions,
    };
};
