define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'site/banner/type/index',
                    add_url: 'site/banner/type/add',
                    edit_url: 'site/banner/type/edit',
                    del_url: 'site/banner/type/del',
                    multi_url: 'site/banner/type/multi',
                    table: 'site_banner_type',
                }
            });

            var table = $("#table");
            var  status_text = {
                '0' : '关闭',
                '1'  : '开启',
            }

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'type', title: __('Type')},
                        {field: 'status', title: '状态',searchList:status_text,formatter:Table.api.formatter.toggle,yes:'1',no:'0'},
                        {field: 'create_time', title: "创建时间", operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: "更新时间", operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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