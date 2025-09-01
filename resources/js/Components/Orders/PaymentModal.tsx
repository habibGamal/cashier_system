import React, { useState } from "react";
import {
    Modal,
    Form,
    InputNumber,
    Radio,
    Button,
    Typography,
    Descriptions,
    message,
} from "antd";
import { router } from "@inertiajs/react";
import { Order, OrderItemData } from "@/types";
import {
    calculateOrderTotals,
    formatCurrency,
} from "@/utils/orderCalculations";
import useLoading from "@/hooks/useLoading";

interface PaymentModalProps {
    open: boolean;
    onCancel: () => void;
    order: Order;
    orderItems: OrderItemData[];
}

export default function PaymentModal({
    open,
    onCancel,
    order,
    orderItems,
}: PaymentModalProps) {
    const [onePaymentForm] = Form.useForm();
    const [multiPaymentForm] = Form.useForm();
    const [paymentMethod, setPaymentMethod] = useState<
        "one_payment" | "multi_payment"
    >("one_payment");
    const [remaining, setRemaining] = useState<number>(0);

    const totals = calculateOrderTotals(order, orderItems);

    const { loading, finish, start } = useLoading();

    const onFinish = (values: any) => {
        // Validation: paid amount(s) must equal total
        if (paymentMethod === "one_payment") {
            if (Number(values.paid) < Math.ceil(totals.total)) {
                message.error("يجب أن يكون المبلغ المدفوع مساويًا للإجمالي");
                return;
            }
        } else {
            const sum = Number(values.cash || 0) + Number(values.card || 0) + Number(values.talabat_card || 0);
            if (sum !== Math.ceil(totals.total)) {
                message.error("يجب أن يكون مجموع المبالغ المدفوعة مساويًا للإجمالي");
                return;
            }
        }
        start();
        let finalValues = { ...values, print: false };

        if (paymentMethod === "one_payment") {
            finalValues = {
                cash: 0,
                card: 0,
                talabat_card: 0,
                print: false,
            };
            finalValues[values.payment_method] = values.paid;
        }

        router.post(`/orders/complete-order/${order.id}`, finalValues, {
            onSuccess: () => {
                message.success("تم إنهاء الطلب بنجاح");
                onCancel();
                router.get(route("orders.index") + `#${order.type}`);
            },
            onFinish: () => {
                finish();
            },
            onError: () => {
                message.error("حدث خطأ أثناء إنهاء الطلب");
            },
        });
    };

    const fullPay = () => {
        onePaymentForm.setFieldsValue({
            paid: Math.ceil(totals.total),
        });
        setRemaining(0);
    };

    const handleCancel = () => {
        onePaymentForm.resetFields();
        multiPaymentForm.resetFields();
        onCancel();
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
        <Modal
            title="الحساب"
            open={open}
            onCancel={handleCancel}
            footer={null}
            width={800}
            destroyOnClose
        >
            <div className="grid grid-cols-2 gap-4">
                <Descriptions bordered column={1} items={paymentItems} />
                <div>
                    <Radio.Group
                        onChange={(e) => setPaymentMethod(e.target.value)}
                        value={paymentMethod}
                        className="mb-4"
                    >
                        <Radio value="one_payment">الدفع بطريقة واحدة</Radio>
                        <Radio value="multi_payment">
                            الدفع باكثر من طريقة
                        </Radio>
                    </Radio.Group>

                    {paymentMethod === "one_payment" && (
                        <Form
                            form={onePaymentForm}
                            onFinish={onFinish}
                            initialValues={{
                                payment_method: "cash",
                                paid: 0,
                            }}
                            layout="vertical"
                        >
                            <Form.Item
                                name="paid"
                                label="المبلغ المدفوع"
                                rules={[
                                    {
                                        required: true,
                                        message: "المبلغ المدفوع مطلوب",
                                    },
                                ]}
                            >
                                <InputNumber
                                    className="w-full"
                                    min={0}
                                    onChange={(value) =>
                                        setRemaining(
                                            totals.total - (value || 0)
                                        )
                                    }
                                />
                            </Form.Item>
                            <Form.Item>
                                <Button onClick={fullPay}>المبلغ كامل</Button>
                            </Form.Item>
                            <Typography.Text className="block mb-4">
                                المبلغ المتبقي : {formatCurrency(remaining)}
                            </Typography.Text>
                            <Form.Item name="payment_method">
                                <Radio.Group>
                                    <Radio value="cash">نقدي</Radio>
                                    <Radio value="card">فيزا</Radio>
                                    <Radio value="talabat_card">
                                        فيزا طلبات
                                    </Radio>
                                </Radio.Group>
                            </Form.Item>
                            <Form.Item>
                                <Button loading={loading} disabled={loading} htmlType="submit" type="primary">
                                    تم
                                </Button>
                            </Form.Item>
                        </Form>
                    )}

                    {paymentMethod === "multi_payment" && (
                        <Form
                            form={multiPaymentForm}
                            onFinish={onFinish}
                            initialValues={{
                                cash: 0,
                                card: 0,
                                talabat_card: 0,
                            }}
                            layout="vertical"
                        >
                            <Form.Item name="cash" label="المبلغ المدفوع كاش">
                                <InputNumber className="w-full" min={0} />
                            </Form.Item>
                            <Form.Item name="card" label="المبلغ المدفوع فيزا">
                                <InputNumber className="w-full" min={0} />
                            </Form.Item>
                            <Form.Item
                                name="talabat_card"
                                label="المبلغ المدفوع فيزا طلبات"
                            >
                                <InputNumber className="w-full" min={0} />
                            </Form.Item>
                            <Form.Item>
                                <Button loading={loading} disabled={loading} htmlType="submit" type="primary">
                                    تم
                                </Button>
                            </Form.Item>
                        </Form>
                    )}
                </div>
            </div>
        </Modal>
    );
}
