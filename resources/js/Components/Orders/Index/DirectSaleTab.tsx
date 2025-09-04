import React, { useEffect, useReducer, useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import {
    App,
    Badge,
    Button,
    Col,
    Descriptions,
    Divider,
    Empty,
    message,
    Row,
    Typography,
} from "antd";
import {
    CheckCircleOutlined,
    EditOutlined,
    PercentageOutlined,
    PrinterOutlined,
    SaveOutlined,
    UserAddOutlined,
} from "@ant-design/icons";

import { OrderItemData, User, Order } from "@/types";
import { orderItemsReducer } from "@/utils/orderItemsReducer";
import {
    calculateOrderTotals,
    formatCurrency,
} from "@/utils/orderCalculations";
import { printOrder } from "../../../helpers/printTemplate";

// Components
import Categories from "@/Components/Orders/Categories";
import OrderItem from "@/Components/Orders/OrderItem";
import CustomerModal from "@/Components/Orders/CustomerModal";
import OrderNotesModal from "@/Components/Orders/OrderNotesModal";
import OrderDiscountModal from "@/Components/Orders/OrderDiscountModal";
import PaymentModal from "@/Components/Orders/PaymentModal";
import IsAdmin from "@/Components/IsAdmin";
import LoadingButton from "@/Components/LoadingButton";
import { useSymbologyScanner } from "@use-symbology-scanner/react";
import {
    StandardSymbologyKey,
} from "@use-symbology-scanner/core/symbologies";

interface DirectSaleTabProps {
    categories: any[];
    existingOrder?: Order;
}

export const DirectSaleTab: React.FC<DirectSaleTabProps> = ({ categories, existingOrder }) => {
    const { auth, receiptFooter, scaleBarcodePrefix } = usePage().props;
    const user = auth.user as User;
    const { modal } = App.useApp();

    // Create initial empty order items
    const products = categories.flatMap((category) => category.products);
    const [orderItems, dispatch] = useReducer(orderItemsReducer, []);
    const [currentOrderId, setCurrentOrderId] = useState<number | null>(existingOrder?.id || null);

    // Create a order object for calculations with proper defaults
    const currentOrder = existingOrder || {
        id: currentOrderId,
        type: 'direct_sale',
        status: 'processing',
        customer: null,
        temp_discount_percent: 0,
        discount: 0,
        service_rate: 0,
    };

    // Modal states
    const [isCustomerModalOpen, setIsCustomerModalOpen] = useState(false);
    const [isOrderNotesModalOpen, setIsOrderNotesModalOpen] = useState(false);
    const [isOrderDiscountModalOpen, setIsOrderDiscountModalOpen] = useState(false);
    const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);

    useEffect(() => {
        if (existingOrder?.items && existingOrder.items.length > 0) {
            // Transform existing order items to OrderItemData format
            const transformedItems: OrderItemData[] = existingOrder.items.map(item => ({
                product_id: item.product_id,
                name: item.product?.name || 'Unknown Product',
                price: item.price,
                quantity: item.quantity,
                notes: item.notes,
                initial_quantity: item.quantity
            }));
            dispatch({ type: "init", orderItems: transformedItems, user });
        } else {
            // Initialize empty order
            dispatch({ type: "init", orderItems: [], user });
        }
    }, [existingOrder]);

    // Calculate totals using current order
    const totals = calculateOrderTotals(currentOrder as any, orderItems);

    const createAndSaveOrder = (
        callback: (page: any) => void = () => {},
        finish: () => void = () => {}
    ) => {
        if (orderItems.length === 0) {
            message.error("يجب إضافة عناصر إلى الطلب قبل الحفظ");
            finish();
            return;
        }

        const itemsForApi = orderItems.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price,
            notes: item.notes || null,
        }));

        router.post(
            route('orders.store'),
            {
                type: 'direct_sale',
                items: itemsForApi
            },
            {
                onSuccess: (page: any) => {
                    message.success("تم إنشاء وحفظ الطلب بنجاح");
                    // Get order ID from the response props
                    const createdOrder = page.props?.order;
                    if (createdOrder?.id) {
                        setCurrentOrderId(createdOrder.id);
                    }
                    callback(page);
                },
                onError: (errors) => {
                    console.error('Save errors:', errors);
                    message.error("فشل في حفظ الطلب");
                },
                onFinish: () => finish(),
            }
        );
    };

    const saveExistingOrder = (
        callback: (page: any) => void = () => {},
        finish: () => void = () => {}
    ) => {
        if (!currentOrderId) {
            createAndSaveOrder(callback, finish);
            return;
        }

        const itemsForApi = orderItems.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price,
            notes: item.notes || null,
        }));

        router.post(
            `/orders/save-order/${currentOrderId}`,
            { items: itemsForApi },
            {
                onSuccess: (page) => {
                    message.success("تم حفظ الطلب بنجاح");
                    callback(page);
                },
                onError: (errors) => {
                    console.error('Save errors:', errors);
                    message.error("فشل في حفظ الطلب");
                },
                onFinish: () => finish(),
            }
        );
    };

    const payment = (finish: () => void) => {
        if (!currentOrderId) {
            createAndSaveOrder(() => setIsPaymentModalOpen(true), finish);
        } else {
            saveExistingOrder(() => setIsPaymentModalOpen(true), finish);
        }
    };

    const printWithCanvas = async (finish: () => void) => {
        if (!currentOrderId) {
            createAndSaveOrder(async (page) => {
                printOrder(
                    page.props.order,
                    orderItems,
                    (receiptFooter as string) || ""
                );
            }, finish);
        } else {
            // For existing orders, we need to get the order data
            saveExistingOrder(async (page) => {
                printOrder(
                    page.props.order,
                    orderItems,
                    (receiptFooter as string) || ""
                );
            }, finish);
        }
    };

    const clearOrder = () => {
        modal.confirm({
            title: "هل أنت متأكد من مسح الطلب؟",
            content: "سيتم فقدان جميع العناصر المضافة",
            okText: "نعم، امسح",
            cancelText: "إلغاء",
            onOk: () => {
                if (currentOrderId) {
                    // Delete the existing order
                    router.delete(route('orders.destroy', currentOrderId), {
                        onSuccess: () => {
                            message.success("تم مسح الطلب");
                            // Refresh the page to reload orders
                            window.location.reload();
                        },
                        onError: () => {
                            message.error("فشل في مسح الطلب");
                        }
                    });
                } else {
                    // Just clear local state if no order exists
                    dispatch({ type: "init", orderItems: [], user });
                    setCurrentOrderId(null);
                    message.success("تم مسح الطلب");
                }
            },
        });
    };

    const paymentItems = [
        {
            key: "1",
            label: "المجموع",
            children: formatCurrency(totals.subTotal),
        },
        {
            key: "2",
            label: "الضريبة",
            children: formatCurrency(totals.tax),
        },
        {
            key: "3",
            label: "الخدمة",
            children: formatCurrency(totals.service),
        },
        {
            key: "4",
            label: "الخصم",
            children: formatCurrency(Number(totals.discount)),
        },
        {
            key: "5",
            label: "الاجمالي",
            children: formatCurrency(totals.total),
        },
    ];

    return (
        <div className="p-4">
            <Row gutter={[16, 16]}>
                <Col span={8}>
                    <div className="isolate grid grid-cols-2 gap-4">
                        <LoadingButton
                            onCustomClick={printWithCanvas}
                            size="large"
                            icon={<PrinterOutlined />}
                        >
                            طباعة الفاتورة
                        </LoadingButton>
                        <Button
                            onClick={() => setIsCustomerModalOpen(true)}
                            size="large"
                            icon={<UserAddOutlined />}
                        >
                            بيانات العميل
                        </Button>
                        <Button
                            onClick={() => setIsOrderNotesModalOpen(true)}
                            size="large"
                            icon={<EditOutlined />}
                        >
                            ملاحظات الطلب
                        </Button>
                        <Button
                            onClick={clearOrder}
                            size="large"
                            danger
                        >
                            مسح الطلب
                        </Button>
                        <IsAdmin>
                            <LoadingButton
                                onCustomClick={(finish) =>
                                    saveExistingOrder(
                                        () => setIsOrderDiscountModalOpen(true),
                                        finish
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
                                saveExistingOrder(undefined, finish)
                            }
                            size="large"
                            icon={<SaveOutlined />}
                            type="primary"
                        >
                            حفظ
                        </LoadingButton>
                        <LoadingButton
                            onCustomClick={payment}
                            size="large"
                            icon={<CheckCircleOutlined />}
                            type="primary"
                        >
                            انهاء الطلب
                        </LoadingButton>
                    </div>

                    <div className="isolate mt-4">
                        <Typography.Title className="mt-0" level={5}>
                            تفاصيل الطلب
                        </Typography.Title>
                        {orderItems.length === 0 && (
                            <Empty
                                image={Empty.PRESENTED_IMAGE_SIMPLE}
                                description="لم يتم إضافة أي عناصر بعد"
                            />
                        )}
                        {orderItems.map((orderItem) => (
                            <OrderItem
                                key={orderItem.product_id}
                                orderItem={orderItem}
                                dispatch={dispatch}
                                disabled={false}
                                user={user}
                            />
                        ))}
                        <Divider />
                        <Descriptions
                            bordered
                            title="الحساب"
                            column={1}
                            items={paymentItems}
                        />
                    </div>
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
            {currentOrderId && (
                <>
                    <CustomerModal
                        open={isCustomerModalOpen}
                        onCancel={() => setIsCustomerModalOpen(false)}
                        order={currentOrder as any}
                    />
                    <OrderNotesModal
                        open={isOrderNotesModalOpen}
                        onCancel={() => setIsOrderNotesModalOpen(false)}
                        order={currentOrder as any}
                    />
                    <OrderDiscountModal
                        open={isOrderDiscountModalOpen}
                        onCancel={() => setIsOrderDiscountModalOpen(false)}
                        order={currentOrder as any}
                    />
                    <PaymentModal
                        open={isPaymentModalOpen}
                        onCancel={() => setIsPaymentModalOpen(false)}
                        order={currentOrder as any}
                        orderItems={orderItems}
                    />
                </>
            )}
        </div>
    );
};

export default DirectSaleTab;
