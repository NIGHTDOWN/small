define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'subject/index',
                    add_url: 'subject/add',
                    edit_url: 'subject/edit',
                    del_url: 'subject/del',
                    multi_url: 'subject/multi',
                    table: 'subject',
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
                        {field: 'subject_name', title: __('Subject_name')},
                        {field: 'create_user_id', title: __('Create_user_id')},
                        {field: 'compere_user_id', title: __('Compere_user_id')},
                        {field: 'cover_img', title: __('Cover_img')},
                        {field: 'weight', title: __('Weight')},
                        {field: 'video_total', title: __('Video_total')},
                        {field: 'video_play_total', title: __('Video_play_total')},
                        {field: 'is_recommend', title: __('Is_recommend')},
                        {field: 'recommend', title: __('Recommend')},
                        {field: 'order_sort', title: __('Order_sort')},
                        {field: 'new_join_time', title: __('New_join_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status')},
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