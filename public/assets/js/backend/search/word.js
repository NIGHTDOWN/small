define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var base_data;
    $.ajax({
        url:'search/word/tableBaseData',
        async:false,
        success:function (data) {
            base_data = data;
        }
    });

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'search/word/index',
                    add_url: 'search/word/add',
                    // edit_url: 'search/word/edit',
                    // del_url: 'search/word/del',
                    multi_url: 'search/word/multi',
                    table: 'search_word',
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
                        {field: 'id', title: __('Id'),sortable:true},
                        {field: 'word', title: __('Word')},
                        {field: 'order_sort', title: __('置顶'),searchList:base_data.order_sort_list,formatter:Table.api.formatter.toggle,yes:'1',no:'0'},
                        {field: 'status', title: __('Status'),searchList:base_data.status_list,formatter:Table.api.formatter.toggle,yes:'1',no:'0'},
                        {field: 'use_count', title: __('Use_count'),sortable:true,operate:false},
                        {field: 'create_time', title: __('Create_time'),sortable:true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});