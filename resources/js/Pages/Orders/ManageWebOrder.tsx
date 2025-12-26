import { Order, Category, User, OrderItemData } from "@/types";
import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Badge,
    Breadcrumb,
    Button,
    Col,
    Descriptions,
    Empty,
    Popconfirm,
    Row,
    Typography,
    message,
    App,
} from "antd";
import {
    CheckCircleOutlined,
    EditOutlined,
    PercentageOutlined,
    PrinterOutlined,
    RightOutlined,
    SaveOutlined,
    UserAddOutlined,
} from "@ant-design/icons";
import { useEffect, useReducer, useState } from "react";
import { orderItemsReducer } from "@/utils/orderItemsReducer";
import { orderStatus } from "@/helpers/orderState";
import { orderHeader } from "@/helpers/orderHeader";
import CashierLayout from "@/Layouts/CashierLayout";
import useModal from "@/hooks/useModal";
import { formatCurrency } from '@/utils/currency';

// Components
import DriverModal from "@/Components/Orders/DriverModal";
import OrderDiscountModal from "@/Components/Orders/OrderDiscountModal";
import OrderNotesModal from "@/Components/Orders/OrderNotesModal";
import WebPaymentModal from "@/Components/Orders/WebPaymentModal";
import OrderItem from "@/Components/Orders/OrderItem";
import IsAdmin from "@/Components/IsAdmin";
import LoadingButton from "@/Components/LoadingButton";

// SOLID Architecture Imports
import { useModalState } from "@/hooks/useModalState";
import { useOrderActions } from "@/hooks/useOrderActions";
import { OrderStrategyFactory } from "@/strategies/OrderStrategies";
import { OrderActionButtons } from "@/Components/Orders/Shared/OrderActionButtons";
import { OrderDetails } from "@/Components/Orders/Shared/OrderDetails";
import { IOrderActions, OrderType } from "@/types/OrderManagement";
import { calculateOrderTotals } from "@/utils/orderCalculations";

interface ManageWebOrderProps {
    order: any;
    categories: any[];
}

export default function ManageWebOrder({
    order,
    categories,
}: ManageWebOrderProps) {
    const { auth } = usePage().props;
    const user = auth.user as User;

    // SOLID: Dependency Injection - Use strategy pattern
    const orderStrategy = OrderStrategyFactory.createStrategy(order.type as OrderType);
    const actionHandlers = useOrderActions(order.type as OrderType);
    const { modalState, modalActions } = useModalState(
        order.type === "web_delivery", // include driver modal
        false // no change order type modal for web orders
    );

    const products = categories.flatMap((category) => category.products);
    const initOrderItems: OrderItemData[] =
        order.items?.map((orderItem: any) => ({
            product_id: orderItem.product_id,
            name: orderItem.product.name,
            price: parseFloat(orderItem.price.toString()),
            quantity: orderItem.quantity,
            notes: orderItem.notes,
            initial_quantity: orderItem.quantity,
        })) || [];

    const [orderItems, dispatch] = useReducer(orderItemsReducer, []);

    // Modal states using useModal hook
    const paymentModal = useModal();

    useEffect(() => {
        dispatch({ type: "init", orderItems: initOrderItems, user });
    }, [order.items]);

    // SOLID: Single Responsibility - Calculate totals and permissions using strategy
    const calculations = calculateOrderTotals(order, orderItems);
    const permissions = orderStrategy.getPermissions(order);

    const acceptOrder = () => {
        router.post(
            `/web-orders/accept-order/${order.id}`,
            {},
            {
                onSuccess: () => message.success("تم قبول الطلب بنجاح"),
                onError: () => message.error("فشل في قبول الطلب"),
            }
        );
    };

    const cancelOrder = () => {
        router.post(
            `/web-orders/reject-order/${order.id}`,
            {},
            {
                onSuccess: () => message.success("تم إلغاء الطلب"),
                onError: () => message.error("فشل في إلغاء الطلب"),
            }
        );
    };

    const outForDelivery = () => {
        router.post(
            `/web-orders/out-for-delivery/${order.id}`,
            {},
            {
                onSuccess: () =>
                    message.success("تم تحديد الطلب كخارج للتوصيل"),
                onError: () => message.error("فشل في تحديث حالة الطلب"),
            }
        );
    };

    // SOLID: Single Responsibility - Define actions using handlers
    const orderActions: IOrderActions = {
        onSave: (finish) => actionHandlers.handleSave(order.id, orderItems, undefined, finish),
        onPayment: (finish) => {
            actionHandlers.handleSave(order.id, orderItems, () => paymentModal.showModal(), finish);
        },
        onPrint: (finish) => actionHandlers.handlePrint(order.id, order, orderItems, finish),
        onDiscount: (finish) => actionHandlers.handleDiscount(order.id, orderItems, modalActions, finish),
    };

    const disableAllControls = !["pending", "processing"].includes(
        order.status
    );
    const isDelivery = order.type === "web_delivery";

    // For web orders, we should allow notes editing even when other controls are disabled
    const disableNotesEditing = !["pending", "processing"].includes(
        order.status
    );

    // Button states logic from original
    const btnsState: Record<
        string,
        (
            | "printReceipt"
            | "driver"
            | "notes"
            | "discount"
            | "save"
            | "cancel"
            | "outForDelivery"
        )[]
    > = {
        pending: ["cancel"],
        processing: [
            "printReceipt",
            "driver",
            "notes",
            "discount",
            "save",
            "cancel",
        ],
        completed: ["cancel", "printReceipt"],
        cancelled: [],
        out_for_delivery: ["printReceipt", "driver", "save", "cancel"],
    };

    const statusInfo = orderStatus(order.status);

    const details = [
        {
            key: "1",
            label: "نوع الطلب",
            children:
                order.type === "web_delivery" ? "ويب دليفري" : "ويب تيك أواي",
        },
        {
            key: "2",
            label: "رقم الطلب المرجعي",
            children: order.id,
        },
        {
            key: "orderNumber",
            label: "رقم الطلب",
            children: order.order_number,
        },
        {
            key: "shiftId",
            label: "رقم الوردية",
            children: order.shift_id,
        },
        {
            key: "3",
            label: "تاريخ الطلب",
            children: new Date(order.created_at).toLocaleString("ar-EG"),
        },
        {
            key: "driver",
            label: "السائق",
            children: order.driver ? order.driver.name : "لا يوجد",
        },
        {
            key: "name",
            label: "اسم العميل",
            children: order.customer ? order.customer.name : "لا يوجد",
        },
        {
            key: "phone",
            label: "رقم العميل",
            children: order.customer ? order.customer.phone : "لا يوجد",
        },
        {
            key: "address",
            label: "عنوان العميل",
            children: order.customer ? order.customer.address : "لا يوجد",
        },
        {
            key: "6",
            label: "ملاحظات",
            children: order.order_notes || "لا توجد ملاحظات",
        },
    ];

    const payments = [
        {
            key: "subTotal",
            label: "المجموع",
            children: formatCurrency(Number(order.sub_total) || 0),
        },
        {
            key: "tax",
            label: "الضريبة",
            children: formatCurrency(Number(order.tax) || 0),
        },
        {
            key: "service",
            label: "الخدمة",
            children: formatCurrency(Number(order.service) || 0),
        },
        {
            key: "discount",
            label: "الخصم",
            children: formatCurrency(Number(order.discount) || 0),
        },
        {
            key: "webPosDiff",
            label: "فرق تسعير",
            children: formatCurrency(Number(order.web_pos_diff) || 0),
        },
        {
            key: "total",
            label: "الإجمالي",
            children: formatCurrency(Number(order.total) || 0),
        },
    ];

    const actionBtn = {
        accept: order.status === "pending",
        outForDelivery:
            order.status === "processing" && order.type === "web_delivery",
        complete:
            (order.status === "out_for_delivery" &&
                order.type === "web_delivery") ||
            (order.status === "processing" && order.type === "web_takeaway"),
    };

    // Custom action buttons for web orders
    const customActions = [
        actionBtn.accept && (
            <Popconfirm
                key="accept"
                title="هل أنت متأكد من قبول الطلب؟"
                okText="نعم"
                cancelText="لا"
                onConfirm={acceptOrder}
            >
                <Button
                    type="primary"
                    size="large"
                    icon={<CheckCircleOutlined />}
                >
                    قبول الطلب
                </Button>
            </Popconfirm>
        ),
        actionBtn.outForDelivery && (
            <Popconfirm
                key="out-for-delivery"
                title="تأكيد؟"
                okText="نعم"
                cancelText="لا"
                onConfirm={outForDelivery}
            >
                <Button
                    type="primary"
                    size="large"
                    icon={<CheckCircleOutlined />}
                >
                    خرج للتوصيل
                </Button>
            </Popconfirm>
        ),
        actionBtn.complete && (
            <Popconfirm
                key="complete"
                title="تأكيد؟"
                okText="نعم"
                cancelText="لا"
                onConfirm={() => orderActions.onPayment?.(() => {})}
            >
                <Button
                    type="primary"
                    size="large"
                    icon={<CheckCircleOutlined />}
                >
                    إنهاء الطلب
                </Button>
            </Popconfirm>
        ),
        (
            <Popconfirm
                key="cancel"
                title="هل أنت متأكد من إلغاء الطلب؟"
                okText="نعم"
                cancelText="لا"
                onConfirm={cancelOrder}
            >
                <Button
                    className="col-span-2"
                    disabled={
                        !btnsState[order.status]?.includes("cancel")
                    }
                    size="large"
                    danger
                >
                    إلغاء
                </Button>
            </Popconfirm>
        ),
    ].filter(Boolean);

    return (
        <CashierLayout title={`إدارة الطلب ${order.order_number}`}>
            <Head title={`طلب رقم ${order.order_number}`} />

            <div className="p-4">
                <Badge.Ribbon color={statusInfo.color} text={statusInfo.text}>
                    <div className="isolate flex gap-4 items-center mb-8">
                        <Button
                            onClick={() => router.get("/orders#" + order.type)}
                            size="large"
                            type="primary"
                            icon={<RightOutlined />}
                        />
                        <Breadcrumb
                            className="text-2xl"
                            separator=">"
                            items={orderHeader(order)}
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
                            customActions={customActions}
                        />

                        {/* SOLID: Single Responsibility - Use dedicated component for order details */}
                        <OrderDetails
                            orderItems={orderItems}
                            dispatch={dispatch}
                            disabled={disableNotesEditing}
                            user={user}
                            calculations={calculations}
                            forWeb={true}
                        />
                    </Col>

                    <Col span="16">
                        <Row gutter={[16, 16]}>
                            <Col span="24">
                                <Descriptions
                                    bordered
                                    title="بيانات الطلب"
                                    column={2}
                                    items={details}
                                />
                            </Col>
                            <Col span="24">
                                <Descriptions
                                    bordered
                                    title="الحساب"
                                    column={2}
                                    items={payments}
                                />
                            </Col>
                        </Row>
                    </Col>
                </Row>
            </div>

            {/* Modals */}
            {modalActions.openDriverModal && (
                <DriverModal
                    open={modalState.isDriverModalOpen || false}
                    onCancel={modalActions.closeDriverModal || (() => {})}
                    order={order}
                />
            )}
            <OrderDiscountModal
                open={modalState.isOrderDiscountModalOpen}
                onCancel={modalActions.closeOrderDiscountModal}
                order={order}
            />
            <WebPaymentModal
                open={paymentModal.open}
                onCancel={paymentModal.onCancel}
                order={order}
            />
            <OrderNotesModal
                open={modalState.isOrderNotesModalOpen}
                onCancel={modalActions.closeOrderNotesModal}
                order={order}
            />
        </CashierLayout>
    );
}
