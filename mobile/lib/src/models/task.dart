import '../utils/task_helpers.dart';

class Task {
  const Task({
    required this.id,
    required this.title,
    required this.priority,
    required this.status,
    this.description = '',
    this.category = '',
    this.dueDate,
    this.endDate,
    this.subtasks = const [],
    this.tags = const [],
    this.isPinned = false,
  });
  factory Task.fromJson(Map<String, dynamic> json) => Task(
    id: json['id'] as int,
    title: json['title'] as String,
    priority: json['priority'] as String,
    status: json['status'] as String,
    description: json['description'] as String? ?? '',
    category: json['category'] as String? ?? '',
    dueDate: DateTime.tryParse(json['due_date'] as String? ?? ''),
    endDate: DateTime.tryParse(json['end_date'] as String? ?? ''),
    subtasks: (json['subtasks'] as List? ?? [])
        .map((item) => Map<String, dynamic>.from(item as Map))
        .toList(),
    tags: (json['tags'] as List? ?? []).map((tag) => '$tag').toList(),
    isPinned: json['is_pinned'] == true,
  );
  final int id;
  final String title, description, priority, status, category;
  final DateTime? dueDate, endDate;
  final List<Map<String, dynamic>> subtasks;
  final List<String> tags;
  final bool isPinned;
  bool get completed => status == 'completed';
  bool get overdue =>
      !completed &&
      dueDate != null &&
      dueDate!.isBefore(dateOnly(DateTime.now()));
  int get completedSubtasks =>
      subtasks.where((s) => s['completed'] == true).length;
  Task copyWith({
    int? id,
    String? title,
    String? description,
    String? priority,
    String? status,
    String? category,
    DateTime? dueDate,
    DateTime? endDate,
    List<Map<String, dynamic>>? subtasks,
    List<String>? tags,
    bool? isPinned,
  }) => Task(
    id: id ?? this.id,
    title: title ?? this.title,
    description: description ?? this.description,
    priority: priority ?? this.priority,
    status: status ?? this.status,
    category: category ?? this.category,
    dueDate: dueDate ?? this.dueDate,
    endDate: endDate ?? this.endDate,
    subtasks: subtasks ?? this.subtasks,
    tags: tags ?? this.tags,
    isPinned: isPinned ?? this.isPinned,
  );
  Map<String, dynamic> toPayload() => {
    'title': title,
    'description': description,
    'priority': priority,
    'status': status,
    'category': category,
    'due_date': apiDate(dueDate),
    'end_date': apiDate(endDate),
    'subtasks': subtasks,
    'tags': tags,
    'is_pinned': isPinned,
  };
}
