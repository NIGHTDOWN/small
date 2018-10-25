define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity.top/index',
                    add_url: 'activity/top/add',
                    edit_url: 'activity/top/edit',
                    del_url: 'activity/top/del',
                    multi_url: 'activity/top/multi',
                    table: 'activity_top',
                }
            });

            var table = $("#table");

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
            },
            formatter: {}
        }
    };
    return Controller;
});