import React, { useState } from 'react';
import { Button, ConfigProvider, Divider, Radio, RadioChangeEvent, Typography } from 'antd';
import { SearchOutlined } from '@ant-design/icons';
import { Input } from 'antd';

import { Category, Product, User, OrderItemAction, OrderItemData } from '@/types';

const { Search } = Input;

interface CategoriesProps {
    categories: Category[];
    dispatch: React.Dispatch<OrderItemAction>;
    disabled?: boolean;
    user: User;
}

export default function Categories({ categories, dispatch, disabled, user }: CategoriesProps) {
    const allProducts = categories.flatMap((category) => category.products);
    const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
    const [products, setProducts] = useState(allProducts);

    const onChangeCategory = ({ target: { value } }: RadioChangeEvent) => {
        if (value === 'all') {
            setProducts(allProducts);
            setSelectedCategory(null);
        } else {
            const category = categories.find((category) => category.id === value);
            setProducts(category?.products || []);
            setSelectedCategory(category?.id || null);
        }
    };

    const onAddProduct = (product: Product) => {
        dispatch({
            type: 'add',
            orderItem: {
                product_id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
                initial_quantity: undefined,
            },
            user,
        });
    };

    const onSearch = (value: string) => {
        const productsToFilter = selectedCategory === null
            ? allProducts
            : categories.find((category) => category.id === selectedCategory)?.products || [];

        setProducts(productsToFilter.filter((product) =>
            product.name.toLowerCase().includes(value.toLowerCase())
        ));
    };

    return (
        <div className="isolate">
            <div className="flex justify-between mb-4">
                <Typography.Title className="mt-0" level={5}>
                    الاصناف
                </Typography.Title>
                <Search
                    style={{ width: 200 }}
                    placeholder="بحث"
                    allowClear
                    onChange={(e) => onSearch(e.target.value)}
                    onSearch={onSearch}
                />
            </div>

            <ConfigProvider
                theme={{
                    token: {
                        borderRadius: 4,
                    },
                }}
            >
                <Radio.Group
                    className="!grid grid-cols-4 text-center gap-4"
                    onChange={onChangeCategory}
                    size="large"
                    defaultValue="all"
                    buttonStyle="solid"
                >
                    <Radio.Button className="rounded before:hidden border-none" value="all">
                        الكل
                    </Radio.Button>
                    {categories.map((category) => (
                        <Radio.Button
                            disabled={disabled}
                            key={category.id}
                            className="rounded whitespace-nowrap overflow-hidden text-ellipsis before:hidden! border-none"
                            value={category.id}
                        >
                            {category.name}
                        </Radio.Button>
                    ))}
                </Radio.Group>
            </ConfigProvider>

            <Divider />

            <div className="grid grid-cols-4 gap-4">
                {products.map((product) => (
                    <Button
                        disabled={disabled}
                        key={product.id}
                        type="primary"
                        onClick={() => onAddProduct(product)}
                        className="w-full h-full min-h-[100px] !text-xl !font-bold whitespace-normal !bg-gray-800"
                        style={{ height: 'auto', minHeight: '100px' }}
                    >
                        {product.name}
                    </Button>
                ))}
            </div>
        </div>
    );
}
