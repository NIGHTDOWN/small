define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cash/operate/index',
                    adopt: 'cash/operate/adopt',
                    refuse: 'cash/operate/refuse',
                    multi_url: 'cash/operate/multi',
                    default_list: 'cash/operate/default_list',
                    table: 'cash_withdraw',
                }
            });

            var table = $("#table");
            var paymentText = {0: '微信', 1: '支付宝'};
            var statusText = {0: '审核中', 1: '已打款', 2: '审核未通过', 3: '已到账', 4: '打款失败', 5: '财务审核通过', 6: '审核通过', 7: '财务审核未通过'};

            // 初始化表格
            table.bootstrapTable({
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {
                            field: 'checkbox',
                            checkbox: false,
                            formatter: function (data) {
                                if (data == true) {
                                    this.checkbox = false;
                                } else {
                                    this.checkbox = true;
                                }
                            }
                        },
                        {field: 'id', title: __('Id')},
                        {field: 'user.nickname', title: __('Nickname')},
                        // 订单号
                        {field: 'order_sn', title: __('Order_sn')},
                        // 提现金额
                        {field: 'apply_price', title: __('Apply_price'), operate:'RANGE', addclass: 'range'},
                        // 申请提现时间
                        {field: 'apply_time', title: __('Apply_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // 支付方式
                        {field: 'payment', title: __('Payment'), searchList: paymentText, formatter: function (data) {return paymentText[data];}},
                        // 状态 0 审核中 1 已打款 2 运营审核未通过  3 已到账 4 打款失败 5 审核通过 6 运营已审核 7 财务审核未通过
                        {field: 'status', title: __('Status'), searchList: statusText, operate: 'IN', formatter: function (data) {return statusText[data];}},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            buttons: [
                                {
                                    name: 'adopt',
                                    title: __('通过审核'),
                                    text: __('通过'),
                                    classname: 'btn btn-xs btn-success btn-adopt',
                                    hidden: function (row) {
                                        if (row.status != 0) {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'refuse',
                                    title: __('拒绝审核'),
                                    text: __('拒绝'),
                                    classname: 'btn btn-xs btn-primary btn-dialog btn-danger btn-refuse',
                                    url: 'cash/operate/refuse',
                                    hidden: function (row) {
                                        if (row.status != 0) {
                                            return true;
                                        }
                                    }
                                }
                            ],
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            // 添加按钮
            Controller.api.buttons(table);
        },
        default_list: function () {
            Controller.api.bindevent();
        },
        adopt: function () {
            Controller.api.bindevent();
        },
        refuse: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            buttons: function (table) {
                //Bootstrap-table的父元素,包含table,toolbar,pagnation
                var parenttable = table.closest('.bootstrap-table');
                //Bootstrap-table配置
                var options = table.bootstrapTable('getOptions');
                //Bootstrap操作区
                var toolbar = $(options.toolbar, parenttable);
                // 通过审核
                $(toolbar).on('click', '.btn-adopt', function () {
                    Layer.confirm(
                        __('确定要通过审核吗?'),
                        {icon: 3, title: __('Warning'), shadeClose: true},
                        function (index) {
                            var ids = Table.api.selectedids(table);
                            Controller.api.method.sendAjax(index, table.extend.adopt, {ids: ids});
                        }
                    );
                });
                // 拒绝审核
                $(toolbar).on('click', '.btn-refuse', function () {
                    var that = this;
                    //循环弹出多个编辑框
                    $.each(table.bootstrapTable('getSelections'), function (index, row) {
                        var url = options.extend.refuse;
                        row = $.extend({}, row ? row : {}, {ids: row[options.pk]});
                        var url = Table.api.replaceurl(url, row, table);
                        Fast.api.open(url, __('拒绝审核'), $(that).data() || {});
                    });
                });
                // 审核理由
                $(toolbar).on('click', '.btn-default_list', function () {
                    Fast.api.open('cash/operate/default_list', __('拒绝理由'));
                });
            },
            events: {
                operate: {
                    // 通过审核
                    'click .btn-adopt': function (e, value, row, index) {
                        Layer.confirm(
                            __('确定要通过审核吗?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(this), shadeClose: true},
                            function (index) {
                                var ids = [row.id];
                                Controller.api.method.sendAjax(index, 'cash/operate/adopt', {ids: ids});
                            }
                        );
                    }
                }
            },
            formatter: {
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);
                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                }
            },
            method: {
                windowSize: function (obj) {
                    var top = $(obj).offset().top - $(window).scrollTop();
                    var left = $(obj).offset().left - $(window).scrollLeft() - 260;
                    if (top + 154 > $(window).height()) {
                        top = top - 154;
                    }
                    if ($(window).width() < 480) {
                        top = left = undefined;
                    }
                    return [top, left];
                },
                sendAjax: function (index, url, data = {}) {
                    Fast.api.ajax({
                        url: url,
                        type: 'POST',
                        data: data
                    }, function (data, result) {
                        Layer.close(index);
                        $('.btn-refresh').trigger('click');
                    }, function (data, result) {
                        Layer.close(index);
                    })
                }
            }
        }
    };
    return Controller;
});