define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'machine_operate/index',
                    add_url: 'machineoperate/add',
                    edit_url: 'machineoperate/edit',
                    del_url: 'machineoperate/del',
                    multi_url: 'machineoperate/multi',
                    table: 'machine_operate',
                }
            });

            var table = $("#table");

            // 版本列表
            var version_list = $.getJSON('machine_operate/versionList');

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false ,
                commonSearch: true,
                searchFormVisible: true,
                columns: [
                    [
                        {field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'day_time', title: __('Day_time'), operate: false},
                        {field: 'channel_name', title: __('Channel_name')},
                        {field: 'register', title: __('Register'), 'operate': false},
                        {field: 'activate', title: __('Activate'), 'operate': false},
                        {field: 'install', title: __('Install'), 'operate': false},
                        {field: 'active', title: __('Active'), 'operate': false},
                        {field: 'version_id', title: __('Version'), searchList: version_list, visible: false},
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