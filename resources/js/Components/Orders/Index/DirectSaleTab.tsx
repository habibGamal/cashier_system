import { usePage } from "@inertiajs/react";
import { App, Col, Row } from "antd";
import React, { useEffect, useReducer, useState } from "react";
import { Page } from "@inertiajs/core";

import { Order, OrderItemData, PageProps, User } from "@/types";
import { calculateOrderTotals } from "@/utils/orderCalculations";
import { orderItemsReducer } from "@/utils/orderItemsReducer";

// Components
import Categories from "@/Components/Orders/Categories";
import CustomerModal from "@/Components/Orders/CustomerModal";
import OrderDiscountModal from "@/Components/Orders/OrderDiscountModal";
import OrderNotesModal from "@/Components/Orders/OrderNotesModal";
import PaymentModal from "@/Components/Orders/PaymentModal";

// SOLID Architecture Imports
import { OrderActionButtons } from "@/Components/Orders/Shared/OrderActionButtons";
import { OrderDetails } from "@/Components/Orders/Shared/OrderDetails";
import { useModalState } from "@/hooks/useModalState";
import { useOrderActions } from "@/hooks/useOrderActions";
import { OrderStrategyFactory } from "@/strategies/OrderStrategies";
import { IOrderActions, OrderType } from "@/types/OrderManagement";
import getDirectSaleOrder from "@/helpers/getDirectSaleOrder";
import { useSymbologyScanner } from "@use-symbology-scanner/react";

interface DirectSaleTabProps {
    categories: any[];
    existingOrder?: Order;
}

export const DirectSaleTab: React.FC<DirectSaleTabProps> = ({
    categories,
    existingOrder,
}) => {
    const { auth, receiptFooter, scaleBarcodePrefix } = usePage().props;
    const user = auth.user as User;
    const { modal } = App.useApp();

    // SOLID: Dependency Injection - Use strategy pattern
    const orderStrategy = OrderStrategyFactory.createStrategy(
        "direct_sale" as OrderType
    );
    const actionHandlers = useOrderActions("direct_sale" as OrderType);
    const { modalState, modalActions } = useModalState();

    // Create initial empty order items
    const products = categories.flatMap((category) => category.products);
    const [orderItems, dispatch] = useReducer(orderItemsReducer, []);
    const [currentOrderId, setCurrentOrderId] = useState<number | null>(
        existingOrder?.id || null
    );
    // Create a order object for calculations with proper defaults
    const currentOrder = existingOrder || {
        id: currentOrderId,
        type: "direct_sale",
        status: "processing",
        customer: null,
        temp_discount_percent: 0,
        discount: 0,
        service_rate: 0,
    };

    useEffect(() => {
        if (existingOrder?.items && existingOrder.items.length > 0) {
            // Transform existing order items to OrderItemData format
            const transformedItems: OrderItemData[] = existingOrder.items.map(
                (item) => ({
                    product_id: item.product_id,
                    name: item.product?.name || "Unknown Product",
                    price: item.price,
                    quantity: item.quantity,
                    notes: item.notes,
                    initial_quantity: item.quantity,
                })
            );
            dispatch({ type: "init", orderItems: transformedItems, user });
        } else {
            // Initialize empty order
            dispatch({ type: "init", orderItems: [], user });
        }
    }, [existingOrder]);

    // SOLID: Single Responsibility - Calculate totals and permissions using strategy
    const calculations = calculateOrderTotals(currentOrder as any, orderItems);
    const permissions = orderStrategy.getPermissions(currentOrder as Order);

    const onSaveCallback = (page: Page<PageProps>) => {};

    const barcodeScanner = (symbol: string, type: any) => {
        const barcode = symbol;
        console.log("Scanned barcode:", barcode);
        dispatch({
            type: "addByBarcode",
            barcode: barcode,
            products: products,
            scalePrefix: scaleBarcodePrefix as string,
            user: user,
        });
    };

    useSymbologyScanner(barcodeScanner, {
        // symbologies,
        scannerOptions: {
            prefix: "",
            suffix: "",
            maxDelay: 100,
        },
    });
    // SOLID: Single Responsibility - Define actions using handlers
    const orderActions: IOrderActions = {
        onSave: (finish) =>
            actionHandlers.handleSave(
                currentOrder.id,
                orderItems,
                onSaveCallback,
                finish
            ),
        onPayment: (finish) => {
            actionHandlers.handlePayment(
                currentOrder.id,
                orderItems,
                modalActions,
                finish
            );
        },
        onPrint: (finish) =>
            actionHandlers.handlePrint(
                currentOrder.id,
                currentOrder as Order,
                orderItems,
                finish
            ),
        onCancel: () =>
            actionHandlers.handleClearOrder(
                currentOrder.id,
                dispatch,
                user,
                setCurrentOrderId
            ),
        onDiscount: (finish) =>
            actionHandlers.handleDiscount(
                currentOrder.id,
                orderItems,
                modalActions,
                finish
            ),
    };

    return (
        <div className="p-4">
            <Row gutter={[16, 16]}>
                <Col span={8}>
                    {/* SOLID: Single Responsibility - Use dedicated component for action buttons */}
                    <OrderActionButtons
                        actions={orderActions}
                        modalActions={modalActions}
                        permissions={permissions}
                    />

                    {/* SOLID: Single Responsibility - Use dedicated component for order details */}
                    <OrderDetails
                        orderItems={orderItems}
                        dispatch={dispatch}
                        disabled={false}
                        user={user}
                        calculations={calculations}
                    />
                </Col>
                <Col span={16}>
                    <Categories
                        disabled={false}
                        categories={categories}
                        dispatch={dispatch}
                        user={user}
                    />
                </Col>
            </Row>

            {/* Modals */}
            {currentOrder.id && (
                <>
                    <CustomerModal
                        open={modalState.isCustomerModalOpen}
                        onCancel={modalActions.closeCustomerModal}
                        order={currentOrder as any}
                    />
                    <OrderNotesModal
                        open={modalState.isOrderNotesModalOpen}
                        onCancel={modalActions.closeOrderNotesModal}
                        order={currentOrder as any}
                    />
                    <OrderDiscountModal
                        open={modalState.isOrderDiscountModalOpen}
                        onCancel={modalActions.closeOrderDiscountModal}
                        order={currentOrder as any}
                    />
                    <PaymentModal
                        open={modalState.isPaymentModalOpen}
                        onCancel={modalActions.closePaymentModal}
                        order={currentOrder as any}
                        orderItems={orderItems}
                    />
                </>
            )}
        </div>
    );
};

export default DirectSaleTab;
