import { router, usePage } from '@inertiajs/react'
import { Button, Form, Input, InputNumber, Modal, Radio, Select, message } from 'antd'
import axios from 'axios'
import React from 'react'
import { Customer, Order, Region } from '@/types'

interface CustomerModalProps {
  open: boolean
  onCancel: () => void
  order: Order
}

export default function CustomerModal({
  open,
  onCancel,
  order,
}: CustomerModalProps) {
  const [form] = Form.useForm()
  const pageProps = usePage().props as any
  const regions = pageProps.regions as Region[] || []

  const saveCustomer = async (values: any) => {
    try {
      const createCustomerRes = await axios.post<Customer>('/quick-customer', values)
      message.success('تم حفظ العميل')
      form.resetFields()
      onCancel()
      router.post(
        `/orders/link-customer/${order.id}`,
        {
          customerId: createCustomerRes.data.id,
        },
        {
          onSuccess: (page) => {
            const data = (page.props.order as Order).customer
            if (!data) return
            form.setFieldsValue({
              name: data.name,
              address: data.address,
              hasWhatsapp: data.hasWhatsapp ? '1' : '0',
              region: data.region,
              deliveryCost: data.deliveryCost,
            })
          },
        }
      )
    } catch (e) {

    }
  }

  const fetchCustomerInfo = async (
    e: React.KeyboardEvent<HTMLInputElement> | React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    e.preventDefault()
    try {
      const response = await axios.post<Customer>('/fetch-customer-info', {
        phone: form.getFieldValue('phone'),
      })
      const data = response.data

      form.setFieldsValue({
        name: data.name,
        address: data.address,
        hasWhatsapp: data.hasWhatsapp ? '1' : '0',
        region: data.region,
        deliveryCost: data.deliveryCost,
      })
    } catch (e: any) {
      if (e.response?.status === 404) return message.error('لم يتم العثور على العميل')
      message.error('حدث خطأ اثناء البحث عن العميل')
    }
  }


  const initialValues = () => {
    if (order.customer) {
      return {
        phone: order.customer.phone,
        name: order.customer.name,
        address: order.customer.address,
        hasWhatsapp: order.customer.hasWhatsapp ? '1' : '0',
        region: order.customer.region,
        deliveryCost: order.customer.deliveryCost,
      }
    }
    return { hasWhatsapp: '0' }
  }
  const updateDeliveryCost = (region: string) => {
    const selectedRegion = regions.find((r) => r.name === region)
    if (selectedRegion) {
      form.setFieldsValue({ deliveryCost: selectedRegion.deliveryCost })
    }
  }
  return (
    <Modal
      open={open}
      onCancel={onCancel}
      title="بيانات العميل"
      footer={null}
      destroyOnClose
    >
      <Form form={form} className="mt-4" onFinish={saveCustomer} initialValues={initialValues()}>
        <div className="flex gap-4">
          <Form.Item name="phone">
            <Input onPressEnter={fetchCustomerInfo} placeholder="رقم العميل" />
          </Form.Item>
          <Button onClick={fetchCustomerInfo} type="primary">
            بحث
          </Button>
        </div>
        <Form.Item name="hasWhatsapp" label="What's app">
          <Radio.Group>
            <Radio value="1">نعم</Radio>
            <Radio value="0">لا</Radio>
          </Radio.Group>
        </Form.Item>
        <Form.Item name="name">
          <Input placeholder="اسم العميل" />
        </Form.Item>
        <Form.Item name="address">
          <Input.TextArea placeholder="العنوان" />
        </Form.Item>
        <Form.Item name="region">
          <Select placeholder="المنطقة" onChange={(value) => updateDeliveryCost(value)}>
            {regions.map((region, key) => (
              <Select.Option key={key} value={region.name}>
                {region.name}
              </Select.Option>
            ))}
          </Select>
        </Form.Item>
        <Form.Item name="deliveryCost">
          <InputNumber style={{ width: '100%' }} placeholder="تكلفة الديليفري" min={0} />
        </Form.Item>

        <div className="flex gap-4">
          <Button htmlType="submit" type="primary">
            حفط
          </Button>
        </div>
      </Form>
    </Modal>
  )
}
