define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cash/withdraw/index',
                    add_url: 'cash/withdraw/add',
                    edit_url: 'cash/withdraw/edit',
                    del_url: 'cash/withdraw/del',
                    multi_url: 'cash/withdraw/multi',
                    table: 'cash_withdraw',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'order_sn', title: __('Order_sn')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'apply_price', title: __('Apply_price')},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'tax', title: __('Tax'), operate:'BETWEEN'},
                        {field: 'service_charge', title: __('Service_charge'), operate:'BETWEEN'},
                        {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                        {field: 'should_money', title: __('Should_money'), operate:'BETWEEN'},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'apply_time', title: __('Apply_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'admin_id', title: __('Admin_id')},
                        {field: 'admin_time', title: __('Admin_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'admin_remark', title: __('Admin_remark')},
                        {field: 'pay_trace_sn', title: __('Pay_trace_sn')},
                        {field: 'payfee_account', title: __('Payfee_account')},
                        {field: 'payfee_real_name', title: __('Payfee_real_name')},
                        {field: 'payment', title: __('Payment')},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'error_msg_id', title: __('Error_msg_id')},
                        {field: 'comment', title: __('Comment')},
                        {field: 'status', title: __('Status')},
                        {field: 'log_id', title: __('Log_id')},
                        {field: 'operator_id', title: __('Operator_id')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});