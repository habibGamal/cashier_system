import React, { useEffect } from "react";
import { Button, Popconfirm } from "antd";
import { usePage } from "@inertiajs/react";
import {
    CheckCircleOutlined,
    EditOutlined,
    PercentageOutlined,
    PrinterOutlined,
    SaveOutlined,
    UserAddOutlined,
} from "@ant-design/icons";
import LoadingButton from "@/Components/LoadingButton";
import { IOrderActions, IModalActions } from "@/types/OrderManagement";
import { PageProps } from "@/types";

interface OrderActionButtonsProps {
    actions: IOrderActions;
    modalActions: IModalActions;
    permissions: {
        canEdit: boolean;
        canSave: boolean;
        canPrint: boolean;
        canCancel: boolean;
        canComplete: boolean;
        canDiscount: boolean;
        canDelete: boolean;
    };
    showDriver?: boolean;
    showChangeOrderType?: boolean;
    customActions?: React.ReactNode[];
}

// Single Responsibility Principle - This component only renders action buttons
export const OrderActionButtons: React.FC<OrderActionButtonsProps> = ({
    actions,
    modalActions,
    permissions,
    showDriver = false,
    showChangeOrderType = false,
    customActions = [],
}) => {
    // Get settings from page props
    const { auth, allowCashierDiscounts, allowCashierCancelOrders } = usePage<PageProps>().props;

    // Add keyboard shortcuts
    useEffect(() => {
        const handleKeyPress = (event: KeyboardEvent) => {
            // Don't trigger shortcuts if user is typing in an input/textarea
            if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
                return;
            }

            // Use Function keys for shortcuts (avoiding F5)
            switch (event.key) {
                case 'F1': // F1 for Save
                    event.preventDefault();
                    if (permissions.canSave && actions.onSave) {
                        actions.onSave(() => { });
                    }
                    break;
                case 'F2': // F2 for Print
                    event.preventDefault();
                    if (permissions.canPrint && actions.onPrint) {
                        actions.onPrint(() => { });
                    }
                    break;
                case 'F3': // F3 for Complete Order
                    event.preventDefault();
                    if (permissions.canComplete && actions.onPayment) {
                        actions.onPayment(() => { });
                    }
                    break;
                case 'F4': // F4 for Discount
                    event.preventDefault();
                    if (permissions.canDiscount && actions.onDiscount) {
                        actions.onDiscount(() => { });
                    }
                    break;
                case 'F6': // F6 for Clear Order (skipping F5)
                    event.preventDefault();
                    if (permissions.canDelete && actions.onCancel) {
                        actions.onCancel();
                    }
                    break;
                case 'F7': // F7 for Customer Modal
                    event.preventDefault();
                    if (permissions.canEdit) {
                        modalActions.openCustomerModal();
                    }
                    break;
                case 'F8': // F8 for Notes Modal
                    event.preventDefault();
                    if (permissions.canEdit) {
                        modalActions.openOrderNotesModal();
                    }
                    break;
                case 'F9': // F9 for Driver Modal
                    event.preventDefault();
                    if (permissions.canEdit && modalActions.openDriverModal) {
                        modalActions.openDriverModal();
                    }
                    break;
            }
        };

        document.addEventListener('keydown', handleKeyPress);
        return () => {
            document.removeEventListener('keydown', handleKeyPress);
        };
    }, [actions, modalActions, permissions]);

    return (
        <div className="isolate grid grid-cols-2 gap-4">
            <LoadingButton
                onCustomClick={actions.onPrint || (() => { })}
                size="large"
                icon={<PrinterOutlined />}
                className="col-span-2"
                disabled={!permissions.canPrint}
                title="F2"
            >
                طباعة الفاتورة
            </LoadingButton>

            <Button
                onClick={modalActions.openCustomerModal}
                disabled={!permissions.canEdit}
                size="large"
                className={showDriver ? "" : "col-span-2"}
                icon={<UserAddOutlined />}
                title="F7"
            >
                بيانات العميل
            </Button>

            {showDriver && modalActions.openDriverModal && (
                <Button
                    onClick={modalActions.openDriverModal}
                    disabled={!permissions.canEdit}
                    size="large"
                    icon={<UserAddOutlined />}
                    title="F9"
                >
                    بيانات السائق
                </Button>
            )}

            <Button
                onClick={modalActions.openOrderNotesModal}
                disabled={!permissions.canEdit}
                size="large"
                icon={<EditOutlined />}
                title="F8"
            >
                ملاحظات الطلب
            </Button>

            {showChangeOrderType && modalActions.openChangeOrderTypeModal && (
                <Button
                    disabled={!permissions.canEdit}
                    onClick={modalActions.openChangeOrderTypeModal}
                    size="large"
                    icon={<EditOutlined />}
                >
                    تغيير الطلب الى
                </Button>
            )}

            {actions.onCancel && (
                <Button
                    onClick={actions.onCancel}
                    size="large"
                    danger
                    disabled={!permissions.canDelete}
                    title="F6"
                >
                    مسح الطلب
                </Button>
            )}

            {/* Discount button - visible to admin always, or to cashier if allowCashierDiscounts is enabled */}
            {(auth.user?.role === 'admin' || allowCashierDiscounts) && (
                <LoadingButton
                    disabled={!permissions.canDiscount}
                    onCustomClick={actions.onDiscount || (() => { })}
                    size="large"
                    icon={<PercentageOutlined />}
                    className="col-span-2"
                    title="F4"
                >
                    خصم
                </LoadingButton>
            )}

            <LoadingButton
                disabled={!permissions.canSave}
                onCustomClick={actions.onSave || (() => { })}
                size="large"
                icon={<SaveOutlined />}
                type="primary"
                title="F1"
            >
                حفظ
            </LoadingButton>

            <LoadingButton
                disabled={!permissions.canComplete}
                onCustomClick={actions.onPayment || (() => { })}
                size="large"
                icon={<CheckCircleOutlined />}
                type="primary"
                title="F3"
            >
                انهاء الطلب
            </LoadingButton>

            {/* Render any custom actions */}
            {customActions.map((action, index) => (
                <React.Fragment key={index}>{action}</React.Fragment>
            ))}
        </div>
    );
};
