DateTime dateOnly(DateTime date) => DateTime(date.year, date.month, date.day);
String? apiDate(DateTime? date) => date == null
    ? null
    : '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
String statusLabel(String status) => switch (status) {
  'in_progress' => 'In progress',
  'completed' => 'Completed',
  _ => 'To do',
};
