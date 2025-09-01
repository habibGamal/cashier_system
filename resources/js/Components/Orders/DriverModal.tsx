import { router, usePage } from '@inertiajs/react'
import { Button, Divider, Form, Input, Modal, Select, message } from 'antd'
import axios from 'axios'
import React from 'react'
import { Driver, Order } from '@/types'

interface DriverModalProps {
  open: boolean
  onCancel: () => void
  order: Order
}

export default function DriverModal({
  open,
  onCancel,
  order,
}: DriverModalProps) {
  const [form] = Form.useForm()

  const pageProps = usePage().props as any
  const drivers = pageProps.drivers as Driver[] || []

  const saveDriver = async (values: any) => {
    try {
      const createDriverRes = await axios.post<Driver>('/quick-driver', values)
      message.success('تم حفظ السائق بنجاح')
      form.resetFields()
      onCancel()
      router.post(
        `/orders/link-driver/${order.id}`,
        {
          driverId: createDriverRes.data.id,
        },
        {
          onSuccess: (page) => {
            const data = (page.props.order as Order).driver
            if (!data) return
            form.setFieldsValue({
              name: data.name,
            })
          },
        }
      )
    } catch (e) {

    }
  }

  const directLink = async (values: any) => {
    try {
      router.post(
        `/orders/link-driver/${order.id}`,
        {
          driverId: values.id,
        },
        {
          onSuccess: (page) => {
            const data = (page.props.order as Order).driver
            if (!data) return
            form.setFieldsValue({
              name: data.name,
            })
            onCancel()
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
      const response = await axios.post<Driver>('/fetch-driver-info', {
        phone: form.getFieldValue('phone'),
      })
      const data = response.data

      form.setFieldsValue({
        name: data.name,
      })
    } catch (e: any) {
      if (e.response?.status === 404) return message.error('لم يتم العثور على السائق')
      message.error('حدث خطأ اثناء البحث عن السائق')
    }
  }


  const initialValues = () => {
    if (order.driver) {
      return {
        phone: order.driver.phone,
        name: order.driver.name,
      }
    }
  }

  return (
    <Modal
      open={open}
      onCancel={onCancel}
      title="بيانات السائق"
      footer={null}
      destroyOnClose
    >
      <Form form={form} className="mt-4" onFinish={saveDriver} initialValues={initialValues()}>
        <Form.Item name="phone">
          <Input onPressEnter={fetchCustomerInfo} placeholder="رقم السائق" />
        </Form.Item>
        <Form.Item name="name">
          <Input placeholder="اسم السائق" />
        </Form.Item>
        <div className="flex gap-4">
          <Button htmlType="submit" type="primary">
            اضافة وحفظ السائق
          </Button>
        </div>
      </Form>
      <Divider />
      <Form className="mt-4" onFinish={directLink}>
        <Form.Item name="id">
          <Select
            placeholder="اختر السائق"
            options={drivers.map((driver) => ({ label: driver.name, value: driver.id }))}
          />
        </Form.Item>
        <Button htmlType="submit" type="primary">
          حفط
        </Button>
      </Form>
    </Modal>
  )
}
