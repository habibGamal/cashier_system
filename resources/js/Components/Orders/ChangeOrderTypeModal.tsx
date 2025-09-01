import React, { useState, useEffect } from 'react';
import { Modal, Form, Radio, message, InputNumber, Spin } from 'antd';
import { router } from '@inertiajs/react';
import { Order } from '@/types';
import { getOrderTypeLabel } from '@/utils/orderCalculations';
import useTableTypes from '@/hooks/useTableTypes';

interface ChangeOrderTypeModalProps {
    open: boolean;
    onCancel: () => void;
    order: Order;
}

export default function ChangeOrderTypeModal({ open, onCancel, order }: ChangeOrderTypeModalProps) {
    const [form] = Form.useForm();
    const [isDineIn, setIsDineIn] = useState(false);
    const { tableTypes, loading, error } = useTableTypes();

    // Update isDineIn state when modal opens or order changes
    useEffect(() => {
        if (open) {
            setIsDineIn(order.type === 'dine_in');
        }
    }, [open, order.type]);

    // Set the first table type as default when dine_in is selected and table types are loaded
    useEffect(() => {
        if (isDineIn && tableTypes.length > 0 && open) {
            const currentValues = form.getFieldsValue();
            if (!currentValues.tableType) {
                form.setFieldsValue({
                    tableType: tableTypes[0].name,
                });
            }
        }
    }, [isDineIn, tableTypes, open, form]);

    const orderTypes = [
        { value: 'dine_in', label: 'صالة' },
        { value: 'takeaway', label: 'تيك أواي' },
        { value: 'delivery', label: 'دليفري' },
        { value: 'companies', label: 'شركات' },
        { value: 'talabat', label: 'طلبات' },
    ];

    const tableOptions = tableTypes.map(tableType => ({
        label: tableType.name,
        value: tableType.name,
    }));

    const onFinish = (values: any) => {
        let submitData = { ...values };

        // If dine_in is selected, format the table number
        if (values.type === 'dine_in' && values.tableType && values.tableNumber) {
            submitData.table_number = `${values.tableType} - ${values.tableNumber}`;
        }

        router.post(`/orders/update-type/${order.id}`, submitData, {
            onSuccess: () => {
                message.success('تم تغيير نوع الطلب بنجاح');
                onCancel();
            },
            onError: () => {
                message.error('حدث خطأ أثناء تغيير نوع الطلب');
            },
        });
    };

    const handleCancel = () => {
        form.resetFields();
        setIsDineIn(false);
        onCancel();
    };

    return (
        <Modal
            title="تغيير نوع الطلب"
            open={open}
            onCancel={handleCancel}
            onOk={() => form.submit()}
            okText="تغيير"
            cancelText="إلغاء"
            destroyOnClose
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={onFinish}
                initialValues={{
                    type: order.type,
                }}
            >
                <Form.Item
                    name="type"
                    label="نوع الطلب الجديد"
                    rules={[{ required: true, message: 'نوع الطلب مطلوب' }]}
                >
                    <Radio.Group
                        onChange={(e) => {
                            const newValue = e.target.value;
                            setIsDineIn(newValue === 'dine_in');

                            // Clear table fields when switching away from dine_in
                            if (newValue !== 'dine_in') {
                                form.setFieldsValue({
                                    tableType: undefined,
                                    tableNumber: undefined,
                                });
                            } else if (tableTypes.length > 0) {
                                // Set default table type when switching to dine_in
                                form.setFieldsValue({
                                    tableType: tableTypes[0].name,
                                });
                            }
                        }}
                    >
                        {orderTypes.map((type) => (
                            <Radio key={type.value} value={type.value}>
                                {type.label}
                            </Radio>
                        ))}
                    </Radio.Group>
                </Form.Item>
                {isDineIn && (
                    <>
                        {loading ? (
                            <div className="flex justify-center items-center py-4">
                                <Spin />
                            </div>
                        ) : (
                            <>
                                <Form.Item
                                    label="نوع الطاولة"
                                    name="tableType"
                                    rules={[{ required: true, message: 'يرجى اختيار نوع الطاولة' }]}
                                >
                                    <Radio.Group
                                        options={tableOptions}
                                        optionType="button"
                                        buttonStyle="solid"
                                    />
                                </Form.Item>
                                <Form.Item
                                    label="رقم الطاولة"
                                    name="tableNumber"
                                    rules={[{ required: true, message: 'يرجى اختيار رقم الطاولة' }]}
                                >
                                    <InputNumber min={1} className="w-full" />
                                </Form.Item>
                                {error && (
                                    <div className="text-red-500 text-sm mt-2">
                                        {error}
                                    </div>
                                )}
                            </>
                        )}
                    </>
                )}
            </Form>
        </Modal>
    );
}
