import React, { useState } from 'react';
import { Modal, Form, InputNumber, Radio, Button, Typography, Descriptions, message, App } from 'antd';
import { PrinterOutlined } from '@ant-design/icons';
import { router } from '@inertiajs/react';
import { Order } from '@/types';
import useModal from '@/hooks/useModal';

interface WebPaymentModalProps {
    open: boolean;
    onCancel: () => void;
    order: Order;
}

// Payment method enum to match original
enum PaymentMethod {
    Cash = 'cash',
    Card = 'card',
    TalabatCard = 'talabat_card',
}

export default function WebPaymentModal({ open, onCancel, order }: WebPaymentModalProps) {
    const [onePaymentForm] = Form.useForm();
    const [multiPaymentForm] = Form.useForm();
    const { modal } = App.useApp();
    const [remaining, setRemaining] = useState<number>(0);
    const [paymentMethod, setPaymentMethod] = useState<'one_payment' | 'multi_payment'>('one_payment');

    const paymentDetails = {
        subTotal: Number(order.sub_total) || 0,
        tax: Number(order.tax) || 0,
        serviceCharge: Number(order.service) || 0,
        discount: Number(order.discount) || 0,
        total: Number(order.total) || 0,
    };

    const orderPaymentItems = (details: typeof paymentDetails) => [
        {
            key: 'subTotal',
            label: 'المجموع',
            children: details.subTotal.toFixed(1),
        },
        {
            key: 'tax',
            label: 'الضريبة',
            children: details.tax.toFixed(1),
        },
        {
            key: 'service',
            label: 'الخدمة',
            children: details.serviceCharge.toFixed(1),
        },
        {
            key: 'discount',
            label: 'الخصم',
            children: details.discount.toFixed(1),
        },
        {
            key: 'total',
            label: 'الاجمالي',
            children: details.total.toFixed(1),
        },
    ];

    const askForPrint = () => {
        completeOrder(
            paymentMethod === 'one_payment'
                ? onePaymentForm.getFieldsValue()
                : multiPaymentForm.getFieldsValue(),
            false
        );
    };

    const completeOrder = (values: any, print: boolean) => {
        if (paymentMethod === 'one_payment') {
            values = {
                ...values,
                [PaymentMethod.Cash]: 0,
                [PaymentMethod.Card]: 0,
                [PaymentMethod.TalabatCard]: 0,
            };
            values[values.paymentMethod] = values.paid;
        }

        if (paymentMethod === 'multi_payment') {
            const paid =
                values[PaymentMethod.Cash] + values[PaymentMethod.Card] + values[PaymentMethod.TalabatCard];

            if (paid < paymentDetails.total && order.type !== 'companies') {
                modal.error({
                    title: 'المبلغ المدفوع غير صحيح',
                    content: 'المبلغ المدفوع غير مطابق للمبلغ المطلوب',
                });
                return;
            }
        }

        router.post(`/web-orders/complete-order/${order.id}`, {
            ...values,
            print,
        }, {
            onSuccess: () => {
                message.success(print ? 'تم إنهاء الطلب وطباعة الفاتورة' : 'تم إنهاء الطلب بنجاح');
                onCancel();
            },
            onError: () => {
                message.error('حدث خطأ أثناء تسجيل الدفع');
            },
        });
    };

    const fullPay = () => {
        onePaymentForm.setFieldsValue({
            paid: Math.ceil(paymentDetails.total),
        });
        setRemaining(0);
    };

    const canPayLater = order.type !== 'companies' ? paymentDetails.total : 0;

    const handleCancel = () => {
        onePaymentForm.resetFields();
        multiPaymentForm.resetFields();
        onCancel();
    };

    return (
        <Modal
            title="الحساب"
            open={open}
            onCancel={handleCancel}
            className="min-w-[800px]"
            footer={null}
            destroyOnClose
        >
            <div className="grid grid-cols-2 gap-4">
                <Descriptions bordered column={1} items={orderPaymentItems(paymentDetails)} />
                <div>
                    <Radio.Group
                        onChange={(e) => {
                            setPaymentMethod(e.target.value);
                        }}
                        value={paymentMethod}
                        className="mb-4"
                    >
                        <Radio value="one_payment">الدفع بطريقة واحدة</Radio>
                        <Radio value="multi_payment">الدفع باكثر من طريقة</Radio>
                    </Radio.Group>

                    <Form
                        form={onePaymentForm}
                        onFinish={askForPrint}
                        initialValues={{
                            discount: 0,
                            paymentMethod: PaymentMethod.Cash,
                        }}
                        layout="vertical"
                        className={`min-w-[350px] ${paymentMethod === 'one_payment' ? 'block' : 'hidden'}`}
                    >
                        <Form.Item
                            name="paid"
                            label="المبلغ المدفوع"
                            rules={[
                                {
                                    required: true,
                                    message: 'المبلغ المدفوع مطلوب',
                                },
                            ]}
                        >
                            <InputNumber
                                className="w-full"
                                min={canPayLater}
                                onChange={(value) => setRemaining(paymentDetails.total - (value || 0))}
                            />
                        </Form.Item>
                        <Form.Item>
                            <Button onClick={fullPay}>المبلغ كامل</Button>
                        </Form.Item>
                        <Typography.Text className="block mb-4">المبلغ المتبقي : {remaining}</Typography.Text>
                        <Form.Item name="paymentMethod">
                            <Radio.Group>
                                <Radio value={PaymentMethod.Cash}>نقدي</Radio>
                                <Radio value={PaymentMethod.Card}>فيزا</Radio>
                                <Radio value={PaymentMethod.TalabatCard}>فيزا طلبات</Radio>
                            </Radio.Group>
                        </Form.Item>
                        <Form.Item>
                            <Button htmlType="submit" type="primary">
                                تم
                            </Button>
                        </Form.Item>
                    </Form>

                    <Form
                        form={multiPaymentForm}
                        onFinish={askForPrint}
                        initialValues={{
                            discount: 0,
                            [PaymentMethod.Cash]: 0,
                            [PaymentMethod.Card]: 0,
                            [PaymentMethod.TalabatCard]: 0,
                        }}
                        layout="vertical"
                        className={`min-w-[350px] ${paymentMethod === 'multi_payment' ? 'block' : 'hidden'}`}
                    >
                        <Form.Item name={PaymentMethod.Cash} label="المبلغ المدفوع كاش">
                            <InputNumber className="w-full" min={0} />
                        </Form.Item>
                        <Form.Item name={PaymentMethod.Card} label="المبلغ المدفوع فيزا">
                            <InputNumber className="w-full" min={0} />
                        </Form.Item>
                        <Form.Item name={PaymentMethod.TalabatCard} label="المبلغ المدفوع فيزا طلبات">
                            <InputNumber className="w-full" min={0} />
                        </Form.Item>
                        <Form.Item>
                            <Button htmlType="submit" type="primary">
                                تم
                            </Button>
                        </Form.Item>
                    </Form>
                </div>
            </div>
        </Modal>
    );
}
