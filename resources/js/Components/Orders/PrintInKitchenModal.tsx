import React, { useState, useEffect } from 'react';
import { Modal, Button, message, Checkbox, Divider, InputNumber } from 'antd';
import { router } from '@inertiajs/react';
import { CheckboxChangeEvent } from 'antd/es/checkbox';
import axios from 'axios';
import { Order, OrderItemData } from '@/types';
import printTemplate from '@/helpers/printTemplate';
import KitchenTemplate, { KitchenItemForPrint } from '../Print/KitchenTemplate';

type CheckboxValueType = string | number | boolean;

interface PrintInKitchenModalProps {
    open: boolean;
    onCancel: () => void;
    order: Order;
    orderItems: OrderItemData[];
}

export default function PrintInKitchenModal({
    open,
    onCancel,
    order,
    orderItems
}: PrintInKitchenModalProps) {
    const [itemsQuantity, setItemsQuantity] = useState<KitchenItemForPrint[]>([]);

    useEffect(() => {
        // Convert OrderItemData to KitchenItemForPrint format
        const convertedItems = orderItems.map(item => ({
            product_id: item.product_id,
            name: item.name,
            price: item.price,
            quantity: item.quantity,
            notes: item.notes,
            initial_quantity: item.quantity
        }));
        setItemsQuantity(JSON.parse(JSON.stringify(convertedItems)));
    }, [orderItems]);

    const defaultList = orderItems.map((item) => ({
        id: item.product_id.toString(),
        label: (
            <div className="my-2">
                {item.name}
                <InputNumber
                    className="mr-2"
                    defaultValue={item.quantity}
                    min={1}
                    onChange={(value) =>
                        setItemsQuantity((state) => {
                            const index = state.findIndex((i) => i.product_id === item.product_id);
                            if (index !== -1) {
                                state[index].quantity = value || 1;
                            }
                            return [...state];
                        })
                    }
                />
            </div>
        ),
        value: item.product_id,
    }));

    const [checkedList, setCheckedList] = useState<CheckboxValueType[]>(
        defaultList.map((item) => item.value)
    );

    const checkAll = defaultList.length === checkedList.length;
    const indeterminate = checkedList.length > 0 && checkedList.length < defaultList.length;

    const onChange = (list: CheckboxValueType[]) => {
        setCheckedList(list);
    };

    const onCheckAllChange = (e: CheckboxChangeEvent) => {
        setCheckedList(e.target.checked ? defaultList.map((item) => item.value) : []);
    };

    const disablePrint = checkedList.length === 0;
    const itemsToPrint = itemsQuantity.filter((item) => checkedList.includes(item.product_id));

    const mappingItemsToPrinters = async () => {
        onCancel();
        message.loading('جاري الطباعة');

        try {
            // Send items data directly to backend for server-side processing
            await axios.post('/print-in-kitchen', {
                orderId: order.id,
                items: itemsToPrint.map(item => ({
                    product_id: item.product_id,
                    name: item.name,
                    quantity: item.quantity,
                    notes: item.notes || null,
                })),
                // Keep images for backward compatibility (not used in new implementation)
                images: [],
            });

            message.success('تم إرسال الطلب للمطبخ بنجاح');
        } catch (error) {
            message.error('حدث خطأ أثناء إرسال الطلب للمطبخ');
        }
    };

    return (
        <Modal
            title="طباعة في المطبخ"
            open={open}
            onCancel={onCancel}
            footer={
                <Button
                    disabled={disablePrint}
                    onClick={mappingItemsToPrinters}
                    className="my-4"
                    htmlType="submit"
                    type="primary"
                >
                    طباعة
                </Button>
            }
            destroyOnClose
        >
            <Checkbox
                indeterminate={indeterminate}
                onChange={onCheckAllChange}
                checked={checkAll}
            >
                طباعة الكل
            </Checkbox>
            <Divider />
            <Checkbox.Group
                className="flex-col text-xl"
                options={defaultList}
                value={checkedList}
                onChange={onChange}
            />
        </Modal>
    );
}
