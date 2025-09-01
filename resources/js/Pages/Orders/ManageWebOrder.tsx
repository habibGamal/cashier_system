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
import { printOrder } from "@/helpers/printTemplate";
import CashierLayout from "@/Layouts/CashierLayout";
import useModal from "@/hooks/useModal";

// Components
import DriverModal from "@/Components/Orders/DriverModal";
import OrderDiscountModal from "@/Components/Orders/OrderDiscountModal";
import OrderNotesModal from "@/Components/Orders/OrderNotesModal";
import WebPaymentModal from "@/Components/Orders/WebPaymentModal";
import PrintInKitchenModal from "@/Components/Orders/PrintInKitchenModal";
import OrderItem from "@/Components/Orders/OrderItem";
import IsAdmin from "@/Components/IsAdmin";
import LoadingButton from "@/Components/LoadingButton";

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
    const orderNotesModal = useModal();
    const orderDiscountModal = useModal();
    const paymentModal = useModal();
    const printInKitchenModal = useModal();
    const driverModal = useModal();

    useEffect(() => {
        dispatch({ type: "init", orderItems: initOrderItems, user });
    }, [order.items]);

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

    const save = (
        callback: (page: any) => void = () => {},
        finish: () => void = () => {}
    ) => {
        // For web orders, we can only update item notes, not quantities
        const itemsWithNotes = orderItems.map((item) => ({
            product_id: item.product_id,
            notes: item.notes,
        }));

        router.post(
            `/web-orders/save-order/${order.id}`,
            {
                items: itemsWithNotes,
            },
            {
                onSuccess: (page) => {
                    message.success("تم حفظ الطلب بنجاح");
                    callback(page);
                },
                onError: () => message.error("فشل في حفظ الطلب"),
                onFinish: () => finish(),
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

    const payment = (finish: () => void = () => {}) => {
        save(() => paymentModal.showModal(), finish);
    };

    const printInKitchen = (finish: () => void = () => {}) => {
        save(() => printInKitchenModal.showModal(), finish);
    };

    const openDiscountModal = (finish: () => void = () => {}) => {
        save(() => orderDiscountModal.showModal(), finish);
    };

    // Keyboard shortcuts
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === "F8") {
                e.preventDefault();
                save();
            }
            if (e.key === "F9") {
                e.preventDefault();
                printWithCanvas();
            }
        };
        window.addEventListener("keydown", handleKeyDown);
        return () => window.removeEventListener("keydown", handleKeyDown);
    }, [orderItems]);

    const printWithCanvas = async (finish: () => void = () => {}) => {
        save(async (page) => {
            await printOrder(
                page.props.order,
                orderItems,
                page.props.receiptFooter?.[0]?.value
            );
        }, finish);
    };

    const getOrderStatus = (status: string) => {
        const statusConfig = {
            pending: { color: "orange", text: "في الإنتظار" },
            processing: { color: "blue", text: "قيد التشغيل" },
            out_for_delivery: { color: "purple", text: "في طريق التوصيل" },
            completed: { color: "green", text: "مكتمل" },
            cancelled: { color: "red", text: "ملغي" },
        };

        return (
            statusConfig[status as keyof typeof statusConfig] || {
                color: "gray",
                text: status,
            }
        );
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
            | "printKitchen"
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
            "printKitchen",
            "driver",
            "notes",
            "discount",
            "save",
            "cancel",
        ],
        completed: ["cancel", "printKitchen", "printReceipt"],
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
            children: `${(Number(order.sub_total) || 0).toFixed(1)} جنيه`,
        },
        {
            key: "tax",
            label: "الضريبة",
            children: `${(Number(order.tax) || 0).toFixed(1)} جنيه`,
        },
        {
            key: "service",
            label: "الخدمة",
            children: `${(Number(order.service) || 0).toFixed(1)} جنيه`,
        },
        {
            key: "discount",
            label: "الخصم",
            children: `${(Number(order.discount) || 0).toFixed(1)} جنيه`,
        },
        {
            key: "webPosDiff",
            label: "فرق تسعير",
            children: `${(Number(order.web_pos_diff) || 0).toFixed(1)} جنيه`,
        },
        {
            key: "total",
            label: "الإجمالي",
            children: `${(Number(order.total) || 0).toFixed(1)} جنيه`,
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
                        <div className="isolate grid grid-cols-2 gap-4">
                            <LoadingButton
                                onCustomClick={printWithCanvas}
                                disabled={
                                    !btnsState[order.status]?.includes(
                                        "printReceipt"
                                    )
                                }
                                size="large"
                                icon={<PrinterOutlined />}
                            >
                                طباعة الفاتورة
                            </LoadingButton>

                            <LoadingButton
                                onCustomClick={printInKitchen}
                                disabled={
                                    !btnsState[order.status]?.includes(
                                        "printKitchen"
                                    )
                                }
                                size="large"
                                icon={<PrinterOutlined />}
                            >
                                طباعة في المطبخ
                            </LoadingButton>

                            {isDelivery && (
                                <Button
                                    onClick={() => driverModal.showModal()}
                                    disabled={
                                        !btnsState[order.status]?.includes(
                                            "driver"
                                        )
                                    }
                                    size="large"
                                    icon={<UserAddOutlined />}
                                >
                                    بيانات السائق
                                </Button>
                            )}

                            <Button
                                onClick={() => orderNotesModal.showModal()}
                                disabled={
                                    !btnsState[order.status]?.includes("notes")
                                }
                                size="large"
                                icon={<EditOutlined />}
                                className={`${isDelivery ? "" : "col-span-2"}`}
                            >
                                ملاحظات الطلب
                            </Button>

                            <IsAdmin>
                                <LoadingButton
                                    onCustomClick={openDiscountModal}
                                    disabled={
                                        !btnsState[order.status]?.includes(
                                            "discount"
                                        )
                                    }
                                    size="large"
                                    icon={<PercentageOutlined />}
                                    className="col-span-2"
                                >
                                    خصم
                                </LoadingButton>
                            </IsAdmin>

                            <LoadingButton
                                onCustomClick={(finish) =>
                                    save(undefined, finish)
                                }
                                disabled={disableAllControls}
                                size="large"
                                icon={<SaveOutlined />}
                                type="primary"
                            >
                                حفظ
                            </LoadingButton>

                            {actionBtn.accept && (
                                <Popconfirm
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
                            )}

                            {actionBtn.outForDelivery && (
                                <Popconfirm
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
                            )}

                            {actionBtn.complete && (
                                <Popconfirm
                                    title="تأكيد؟"
                                    okText="نعم"
                                    cancelText="لا"
                                    onConfirm={() => payment()}
                                >
                                    <Button
                                        type="primary"
                                        size="large"
                                        icon={<CheckCircleOutlined />}
                                    >
                                        إنهاء الطلب
                                    </Button>
                                </Popconfirm>
                            )}

                            <Popconfirm
                                title="هل أنت متأكد من إلغاء الطلب؟"
                                okText="نعم"
                                cancelText="لا"
                                onConfirm={cancelOrder}
                            >
                                <Button
                                    className="col-span-2"
                                    disabled={
                                        !btnsState[order.status]?.includes(
                                            "cancel"
                                        )
                                    }
                                    size="large"
                                    danger
                                >
                                    إلغاء
                                </Button>
                            </Popconfirm>
                        </div>

                        <div className="isolate mt-4">
                            <Typography.Title className="mt-0" level={5}>
                                تفاصيل الطلب
                            </Typography.Title>
                            {orderItems.length === 0 && (
                                <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
                            )}
                            {orderItems.map((orderItem) => (
                                <OrderItem
                                    key={orderItem.product_id}
                                    orderItem={orderItem}
                                    dispatch={dispatch}
                                    disabled={disableNotesEditing}
                                    user={user}
                                    forWeb={true}
                                />
                            ))}
                        </div>
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
            <DriverModal
                open={driverModal.open}
                onCancel={driverModal.onCancel}
                order={order}
            />
            <OrderDiscountModal
                open={orderDiscountModal.open}
                onCancel={orderDiscountModal.onCancel}
                order={order}
            />
            <WebPaymentModal
                open={paymentModal.open}
                onCancel={paymentModal.onCancel}
                order={order}
            />
            <OrderNotesModal
                open={orderNotesModal.open}
                onCancel={orderNotesModal.onCancel}
                order={order}
            />
            <PrintInKitchenModal
                open={printInKitchenModal.open}
                onCancel={printInKitchenModal.onCancel}
                order={order}
                orderItems={orderItems}
            />
        </CashierLayout>
    );
}
