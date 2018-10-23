define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var base_data;
    $.ajax({
        url:'video/video/tableBaseData',
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
                    index_url: 'video/video/index',
                    del_url: 'video/video/del',
                    table: 'video'
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
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'title', title: __('Title')},
                        {field: 'extend.cover_imgs', title: __('封面'),operate: false,formatter: Table.api.formatter.image},
                        {field: 'category_id', title: __('分类'), searchList: base_data.categoryList, formatter: function (value){return base_data.categoryList[value];}},
                        {field: 'user.nickname', title: __('用户昵称'),operate:'=',column:'user_id',addclass:'selectpage', data:'data-source="user/user/selectpage"  data-params=""  data-field="nickname"'},
                        {field: 'user_like_total', title: __('点赞'), operate: false, sortable: true},
                        {field: 'user_comment_total', title: __('评论'), operate: false, sortable: true},
                        {field: 'user_view_total', title: __('播放'), operate: false, sortable: true},
                        {field: 'subjects', title: __('标签'), operate: 'FIND_IN_SET',column:'subject_ids',addclass:'selectpage', data:'data-source="subject/selectpage" data-field="subject_name"',formatter:function (arr) {
                            var subjects='';
                            arr.map(function(value,index){
                                if (index){
                                    subjects+=(','+value.subject_name);
                                }else {
                                    subjects+=value.subject_name;
                                }
                            });
                            return subjects;
                        }},
                        {field: 'status', title: __('Status'), searchList: base_data.statusList, formatter: function (value) {return base_data.statusList[value]}},
                        {field: 'create_time', title: __('Create_time'), sortable: true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), sortable: true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'process_status', title: __('转码状态'), operate:'=',searchList:{0:'完成',1:'处理中'},formatter: function (value) {
                            return value?'处理中':'完成';
                        }},
                        {field: 'process_done_time', title: __('转码完成时间'),visible:false, sortable: true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'hotvideo.status', title: __('是否热门'),visible:false, operate: '=',searchList:{1:'是'},formatter:function (value) {
                            return value?'是':'否';
                        }},
                        {field: 'hotvideo.create_time', title: __('添加热门时间'),visible:false, sortable: true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'editcategory',
                                    title: __('编辑分类'),
                                    text: __('编辑分类'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    url: 'video/video/editcategory'
                                },
                                {
                                    name: 'editcoverimg',
                                    title: __('编辑封面'),
                                    text: __('编辑封面'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    url: 'video/video/editcoverimg'
                                },
                                {
                                    name: 'edittitle',
                                    title: __('编辑标题'),
                                    text: __('编辑标题'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    url: 'video/video/edittitle'
                                },
                                {
                                    name: 'addlike',
                                    title: __('增加点赞'),
                                    text: __('增加点赞'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    url: 'video/video/addlike'
                                },
                                {
                                    name: 'play',
                                    title: __('查看视频'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-play',
                                    url: 'video/video/play'
                                },
                                {
                                    name: 'checkpass',
                                    title: __('审核通过'),
                                    text: __('审核通过'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'video/video/checkpass',
                                    confirm:'确认通过?',
                                    hidden: function(row) {
                                        if (row.process_status === 0){
                                            if (row.status === 2 || row.status === 8) {
                                                return false;
                                            }
                                        }
                                        return true;
                                    }
                                },
                                {
                                    name: 'checknopass',
                                    title: __('审核不通过'),
                                    text: __('审核不通过'),
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    url: 'video/video/checknopass',
                                    hidden: function(row) {
                                        if (row.process_status === 0){
                                            if (row.status === 2 || row.status === 8) {
                                                return false;
                                            }
                                        }
                                        return true;
                                    }
                                },
                                {
                                    name: 'show',
                                    title: __('上架'),
                                    text: __('上架'),
                                    confirm:'确认上架?',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'video/video/show',
                                    hidden: function(row) {
                                        if (row.process_status === 0){
                                            if (row.status === 0) {
                                                return false;
                                            }
                                        }
                                        return true;
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                },
                                {
                                    name: 'hide',
                                    title: __('下架'),
                                    text: __('下架'),
                                    confirm:'确认下架?',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    url: 'video/video/hide',
                                    hidden: function(row) {
                                        if (row.process_status === 0){
                                            if (row.status === 1) {
                                                return false;
                                            }
                                        }
                                        return true;
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                },
                                {
                                    name: 'del',
                                    title: __('删除'),
                                    text: __('删除'),
                                    classname: 'btn btn-xs btn-danger btn-dialog',
                                    url: 'video/video/del'
                                },
                                {
                                    name: 'top',
                                    title: __('置顶'),
                                    text: __('置顶'),
                                    confirm:'确认置顶?',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'video/video/top/action/1',
                                    hidden: function(row) {
                                        if (row.category_id){
                                            if (row.recommend === 0){
                                                return false;
                                            }
                                        }
                                        return true;
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                },
                                {
                                    name: 'cancel_top',
                                    title: __('取消置顶'),
                                    text: __('取消置顶'),
                                    confirm:'确认取消置顶?',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    url: 'video/video/top/action/0',
                                    hidden: function(row) {
                                        if (row.category_id){
                                            if (row.recommend === 1){
                                                return false;
                                            }
                                        }
                                        return true;
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                },
                                {
                                    name: 'hot',
                                    title: __('热门'),
                                    text: __('热门'),
                                    confirm:'确认添加热门?',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'video/video/hot/action/1',
                                    hidden: function(row) {
                                        if (row.status === 1){
                                            if (!row.hotvideo||row.hotvideo.status == 0){
                                                return false;
                                            }
                                        }
                                        return true;
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                },
                                {
                                    name: 'cancel_hot',
                                    title: __('取消热门'),
                                    text: __('取消热门'),
                                    confirm:'确认取消热门?',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    url: 'video/video/hot/action/0',
                                    hidden: function(row) {
                                        if (row.hotvideo&&row.hotvideo.status == 1){
                                            return false;
                                        }
                                        return true;
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
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
        edit: function () {
            Controller.api.bindevent();
        },
        edittitle: function () {
            Controller.api.bindevent();
        },
        editcategory: function () {
            Controller.api.bindevent();
        },
        addlike: function () {
            Controller.api.bindevent();
        },
        editcoverimg: function () {
            Controller.api.bindevent();
        },
        checknopass: function () {
            Controller.api.bindevent();
        },
        del: function () {
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