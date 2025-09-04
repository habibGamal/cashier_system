import {
    RightOutlined
} from "@ant-design/icons";
import { Head, router, usePage } from "@inertiajs/react";
import {
    App,
    Badge,
    Breadcrumb,
    Button,
    Col,
    Popconfirm,
    Row
} from "antd";
import { useEffect, useReducer } from "react";

import CashierLayout from "@/Layouts/CashierLayout";
import { ManageOrderProps, OrderItemData, User } from "@/types";
import {
    calculateOrderTotals,
    getOrderStatusConfig,
    getOrderTypeLabel
} from "@/utils/orderCalculations";
import { orderItemsReducer } from "@/utils/orderItemsReducer";

// Components
import IsAdmin from "@/Components/IsAdmin";
import Categories from "@/Components/Orders/Categories";
import ChangeOrderTypeModal from "@/Components/Orders/ChangeOrderTypeModal";
import CustomerModal from "@/Components/Orders/CustomerModal";
import DriverModal from "@/Components/Orders/DriverModal";
import OrderDiscountModal from "@/Components/Orders/OrderDiscountModal";
import OrderNotesModal from "@/Components/Orders/OrderNotesModal";
import PaymentModal from "@/Components/Orders/PaymentModal";
import { useSymbologyScanner } from "@use-symbology-scanner/react";

// SOLID Architecture Imports
import { OrderActionButtons } from "@/Components/Orders/Shared/OrderActionButtons";
import { OrderDetails } from "@/Components/Orders/Shared/OrderDetails";
import { useModalState } from "@/hooks/useModalState";
import { useOrderActions } from "@/hooks/useOrderActions";
import { OrderStrategyFactory } from "@/strategies/OrderStrategies";
import { IOrderActions, OrderType } from "@/types/OrderManagement";

export default function ManageOrder({
    order,
    categories,
    drivers,
    regions,
}: ManageOrderProps) {
    const { auth, receiptFooter, scaleBarcodePrefix } = usePage().props;
    const user = auth.user as User;
    const { modal } = App.useApp();

    // SOLID: Dependency Injection - Use strategy pattern
    const orderStrategy = OrderStrategyFactory.createStrategy(order.type as OrderType);
    const actionHandlers = useOrderActions(order.type as OrderType);
    const { modalState, modalActions } = useModalState(
        order.type === "delivery", // include driver modal
        true // include change order type modal
    );

    // Create initial order items from the order
    const products = categories.flatMap((category: any) => category.products);
    const initOrderItems: OrderItemData[] = order.items.map((orderItem: any) => ({
        product_id: orderItem.product_id,
        name:
            products.find((product: any) => product.id === orderItem.product_id)
                ?.name || "",
        price: parseFloat(orderItem.price.toString()),
        quantity: orderItem.quantity,
        notes: orderItem.notes,
        initial_quantity: orderItem.quantity,
    }));

    const [orderItems, dispatch] = useReducer(orderItemsReducer, []);

    const barcodeScanner = (symbol: string, type: any) => {
        const barcode = symbol;
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
            maxDelay: 50,
        },
    });

    useEffect(() => {
        dispatch({ type: "init", orderItems: initOrderItems, user });
    }, [order.items]);

    // SOLID: Single Responsibility - Calculate totals and permissions using strategy
    const calculations = calculateOrderTotals(order, orderItems);
    const permissions = orderStrategy.getPermissions(order);

    // Order state checks
    const disableAllControls = order.status !== "processing";
    const orderCancelled = order.status === "cancelled";
    const orderCompleted = order.status === "completed";
    const isDelivery = order.type === "delivery";

    // SOLID: Single Responsibility - Define actions using handlers
    const orderActions: IOrderActions = {
        onSave: (finish) => actionHandlers.handleSave(order.id, orderItems, undefined, finish),
        onPayment: (finish) => actionHandlers.handlePayment(order.id, orderItems, modalActions, finish),
        onPrint: (finish) => actionHandlers.handlePrint(order.id, order, orderItems, finish),
        onDiscount: (finish) => actionHandlers.handleDiscount(order.id, orderItems, modalActions, finish),
    };

    const cancelOrder = () => {
        router.post(`/orders/cancel-order/${order.id}`);
    };

    const orderStatusConfig = getOrderStatusConfig(order.status);
    const breadcrumbItems = [
        {
            title: `${getOrderTypeLabel(order.type)}`,
        },
        {
            title: `طلب رقم ${order.order_number} `,
        },
    ];

    // Custom action buttons for this specific component
    const customActions = [
        orderCompleted && (
            <IsAdmin key="admin-cancel">
                <Popconfirm
                    title="هل انت متأكد من الغاء الطلب؟"
                    okText="نعم"
                    cancelText="لا"
                    onConfirm={cancelOrder}
                >
                    <Button
                        className="col-span-2"
                        disabled={orderCancelled}
                        size="large"
                        danger
                    >
                        الغاء
                    </Button>
                </Popconfirm>
            </IsAdmin>
        ),
    ].filter(Boolean);

    return (
        <CashierLayout title={`إدارة الطلب ${order.order_number}`}>
            <Head title={`إدارة الطلب ${order.order_number}`} />

            <div className="p-4">
                <Badge.Ribbon
                    color={orderStatusConfig.color}
                    text={orderStatusConfig.text}
                >
                    <div className="isolate flex gap-4 items-center">
                        <Button
                            onClick={() => router.get("/orders#" + order.type)}
                            size="large"
                            type="primary"
                            icon={<RightOutlined />}
                        />
                        <Breadcrumb
                            className="text-2xl"
                            separator=">"
                            items={breadcrumbItems}
                        />
                    </div>
                </Badge.Ribbon>

                <Row gutter={[16, 16]} className="mt-8">
                    <Col span={8}>
                        {/* SOLID: Single Responsibility - Use dedicated component for action buttons */}
                        <OrderActionButtons
                            actions={orderActions}
                            modalActions={modalActions}
                            permissions={permissions}
                            showDriver={isDelivery}
                            showChangeOrderType={true}
                            customActions={customActions}
                        />

                        {/* SOLID: Single Responsibility - Use dedicated component for order details */}
                        <OrderDetails
                            orderItems={orderItems}
                            dispatch={dispatch}
                            disabled={disableAllControls}
                            user={user}
                            calculations={calculations}
                        />
                    </Col>
                    <Col span={16}>
                        <Categories
                            disabled={disableAllControls}
                            categories={categories}
                            dispatch={dispatch}
                            user={user}
                        />
                    </Col>
                </Row>

                {/* Modals */}
                <CustomerModal
                    open={modalState.isCustomerModalOpen}
                    onCancel={modalActions.closeCustomerModal}
                    order={order}
                />
                {modalActions.openDriverModal && (
                    <DriverModal
                        open={modalState.isDriverModalOpen || false}
                        onCancel={modalActions.closeDriverModal || (() => {})}
                        order={order}
                    />
                )}
                <OrderNotesModal
                    open={modalState.isOrderNotesModalOpen}
                    onCancel={modalActions.closeOrderNotesModal}
                    order={order}
                />
                {modalActions.openChangeOrderTypeModal && (
                    <ChangeOrderTypeModal
                        open={modalState.isChangeOrderTypeModalOpen || false}
                        onCancel={modalActions.closeChangeOrderTypeModal || (() => {})}
                        order={order}
                    />
                )}
                <OrderDiscountModal
                    open={modalState.isOrderDiscountModalOpen}
                    onCancel={modalActions.closeOrderDiscountModal}
                    order={order}
                />
                <PaymentModal
                    open={modalState.isPaymentModalOpen}
                    onCancel={modalActions.closePaymentModal}
                    order={order}
                    orderItems={orderItems}
                />
            </div>
        </CashierLayout>
    );
}
