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
                    // multi_url: 'search/word/multi',
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
                        {field: 'order_sort', title: __('置顶'),searchList:base_data.order_sort_list,formatter: function (value) {
                            return base_data.order_sort_list[value];
                        }},
                        {field: 'status', title: __('Status'),searchList:base_data.status_list,formatter: function (value) {
                            return base_data.status_list[value];
                        }},
                        {field: 'use_count', title: __('Use_count'),sortable:true,operate:false},
                        {field: 'create_time', title: __('Create_time'),sortable:true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'set_top',
                                    text: __('置顶'),
                                    title: __('置顶'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'search/word/top/order_sort/1',
                                    confirm: '确认置顶?',
                                    hidden:function (row) {
                                        if (row.order_sort == 1){
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    },
                                    error: function (data, ret) {
                                        return false;
                                    }
                                },
                                {
                                    name: 'cancel_top',
                                    text: __('取消置顶'),
                                    title: __('取消置顶'),
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'search/word/top/order_sort/0',
                                    confirm: '确认取消置顶?',
                                    hidden:function (row) {
                                        if (row.order_sort == 0){
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    },
                                    error: function (data, ret) {
                                        return false;
                                    }
                                },
                                {
                                    name: 'show',
                                    text: __('显示'),
                                    title: __('显示'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'search/word/editStatus/status/1',
                                    confirm: '确认显示?',
                                    hidden:function (row) {
                                        if (row.status == 1){
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    },
                                    error: function (data, ret) {
                                        return false;
                                    }
                                },
                                {
                                    name: 'hide',
                                    text: __('隐藏'),
                                    title: __('隐藏'),
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'search/word/editStatus/status/0',
                                    confirm: '确认隐藏?',
                                    hidden:function (row) {
                                        if (row.status == 0){
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    },
                                    error: function (data, ret) {
                                        return false;
                                    }
                                }
                            ]
                        }
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