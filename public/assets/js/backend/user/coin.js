define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/coin/index',
                    set_param: 'user/coin/set_param',
                    table: 'user_coin',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user.nickname', title: __('昵称'), operate:'=', column:'user_id', addclass:'selectpage', data:'data-source="user/user/selectpage"  data-params=""  data-field="nickname"'},
                        {field: 'amount', title: __('Amount')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'reason', title: __('Reason'), operate: false},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            Controller.api.buttons(table);
        },
        api: {
            bindevent: function () {
                //当表格数据加载完成时
                Form.api.bindevent($("form[role=form]"));
            },
            buttons: function (table) {
                // Bootstrap-table的父元素,包含table,toolbar,pagnation
                var parenttable = table.closest('.bootstrap-table');
                // Bootstrap-table配置
                var options = table.bootstrapTable('getOptions');
                // Bootstrap操作区
                var toolbar = $(options.toolbar, parenttable);
                // 设置参数
                $(toolbar).on('click', '.btn-set_param', function () {
                    var url = options.extend.set_param;
                    Fast.api.open(url, __('设置参数'));
                });
                //当表格数据加载完成时
                table.on('load-success.bs.table', function (e, data) {
                    $('#coin_user_total').text(data.coin_total.coin_user_total);
                    $('#raise_coin_total').text(data.coin_total.raise_coin_total);
                    $('#consume_total').text(data.coin_total.consume_total);
                    $('#withdraw_total').text(data.coin_total.withdraw_total);
                });
            }
        }
    };
    return Controller;
});