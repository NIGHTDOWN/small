define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cash/withdraw/index',
                    del_url: 'cash/withdraw/del',
                    multi_url: 'cash/withdraw/multi',
                    table: 'cash_withdraw',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'cash_withdraw.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        // 订单号
                        {field: 'order_sn', title: __('Order_sn')},
                        // 提现金额
                        {field: 'apply_price', title: __('Apply_price'),formatter: function (data) {return data + '元';}},
                        // 申请提现时间
                        {field: 'apply_time', title: __('Apply_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // 支付方式
                        {
                            field: 'payment', 
                            title: __('Payment'),
                            searchList: {
                                '0': '支付宝',
                                '1': '微信'
                            },
                            formatter: function (data) {
                                if (data == '0') {
                                    return '支付宝';
                                } else {
                                    return '微信';
                                }
                            }
                        },
                        // 状态 0 审核中 1 已打款 2 运营审核未通过  3 已到账 4 打款失败 5 审核通过 6 运营已审核 7 财务审核未通过
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {
                                '1': '已打款',
                                '3': '已到账',
                                '4': '打款失败',
                                '5': '审核通过',
                                '6': '审核中',
                                '7': '审核未通过',
                            },
                            formatter: function (data) {
                                switch (data) { 
                                    case 1:var status="已打款"; 
                                    break; 
                                    case 3:var status="已到账"; 
                                    break; 
                                    case 4:var status="打款失败"; 
                                    break; 
                                    case 5:var status="审核通过"; 
                                    break; 
                                    case 6:var status="审核中"; 
                                    break; 
                                    case 7:var status="审核未通过"; 
                                    break;
                                }
                                return status; 
                            }
                        },
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});