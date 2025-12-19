import React, { useState } from 'react';
import { Modal, Form, InputNumber, Radio, message, Button } from 'antd';
import { OrderItemData, OrderItemAction, User } from '@/types';

interface ItemDiscountModalProps {
    open: boolean;
    onCancel: () => void;
    orderItem: OrderItemData;
    dispatch: React.Dispatch<OrderItemAction>;
    user: User;
}

export default function ItemDiscountModal({
    open,
    onCancel,
    orderItem,
    dispatch,
    user,
}: ItemDiscountModalProps) {
    const [form] = Form.useForm();

    const itemSubtotal = orderItem.price * orderItem.quantity;

    const onFinish = (values: { discount_type: string; discount_value: number }) => {
        const { discount_type, discount_value } = values;

        let discount = 0;
        let discountPercent: number | undefined;

        if (discount_type === 'percent') {
            discountPercent = discount_value;
            discount = itemSubtotal * (discount_value / 100);
        } else {
            discount = discount_value;
        }

        // Validate discount doesn't exceed item subtotal
        if (discount > itemSubtotal) {
            message.error('الخصم لا يمكن أن يتجاوز إجمالي الصنف');
            return;
        }

        dispatch({
            type: 'changeItemDiscount',
            id: orderItem.product_id,
            discount: discount,
            discountType: discount_type,
            discountPercent: discountPercent,
            user,
        });

        message.success('تم تطبيق الخصم على الصنف');
        form.resetFields();
        onCancel();
    };

    const handleCancel = () => {
        form.resetFields();
        onCancel();
    };

    const handleRemoveDiscount = () => {
        dispatch({
            type: 'changeItemDiscount',
            id: orderItem.product_id,
            discount: 0,
            discountType: 'value',
            discountPercent: undefined,
            user,
        });

        message.success('تم إزالة الخصم من الصنف');
        form.resetFields();
        onCancel();
    };

    return (
        <Modal
            title={`خصم على: ${orderItem.name}`}
            open={open}
            onCancel={handleCancel}
            onOk={() => form.submit()}
            okText="تطبيق"
            cancelText="إلغاء"
            destroyOnClose
            footer={[
                <Button
                    key="remove"
                    danger
                    onClick={handleRemoveDiscount}
                    disabled={!orderItem.item_discount || orderItem.item_discount === 0}
                >
                    إزالة الخصم
                </Button>,
                <Button
                    key="cancel"
                    onClick={handleCancel}
                >
                    إلغاء
                </Button>,
                <Button
                    key="submit"
                    type="primary"
                    onClick={() => form.submit()}
                >
                    تطبيق
                </Button>,
            ]}
        >
            <div className="mb-4 text-gray-500">
                إجمالي الصنف: {itemSubtotal.toFixed(2)} ج.م
            </div>

            <Form
                form={form}
                layout="vertical"
                onFinish={onFinish}
                initialValues={{
                    discount_value: orderItem.item_discount_type === 'percent'
                        ? orderItem.item_discount_percent ?? 0
                        : orderItem.item_discount ?? 0,
                    discount_type: orderItem.item_discount_type ?? 'value',
                }}
            >
                <Form.Item
                    name="discount_type"
                    label="نوع الخصم"
                >
                    <Radio.Group>
                        <Radio value="value">مبلغ ثابت</Radio>
                        <Radio value="percent">نسبة مئوية</Radio>
                    </Radio.Group>
                </Form.Item>

                <Form.Item
                    noStyle
                    shouldUpdate={(prevValues, currentValues) =>
                        prevValues.discount_type !== currentValues.discount_type
                    }
                >
                    {({ getFieldValue }) => {
                        const discountType = getFieldValue('discount_type');
                        const max = discountType === 'percent' ? 100 : itemSubtotal;
                        const suffix = discountType === 'percent' ? '%' : 'ج.م';

                        return (
                            <Form.Item
                                name="discount_value"
                                label="قيمة الخصم"
                                rules={[
                                    { required: true, message: 'قيمة الخصم مطلوبة' },
                                    {
                                        type: 'number',
                                        min: 0,
                                        max: max,
                                        message: `القيمة يجب أن تكون بين 0 و ${max}`,
                                    },
                                ]}
                            >
                                <InputNumber
                                    min={0}
                                    max={max}
                                    style={{ width: '100%' }}
                                    placeholder={`ادخل قيمة الخصم (${suffix})`}
                                    addonAfter={suffix}
                                />
                            </Form.Item>
                        );
                    }}
                </Form.Item>
            </Form>
        </Modal>
    );
}
