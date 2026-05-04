# cgpa_engine.py - Flask API that powers the CGPA calculator
# Install dependencies: pip install flask flask-cors reportlab
# Run with: python cgpa_engine.py
# Runs on: http://localhost:5000

from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.units import cm
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, HRFlowable
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT
import io
import json
from datetime import datetime

app = Flask(__name__)
CORS(app)

# ── Grade Logic ────────────────────────────────────────────────────────────────

def get_grade_and_point(score):
    if score >= 70:
        return 'A', 5
    elif score >= 60:
        return 'B', 4
    elif score >= 50:
        return 'C', 3
    elif score >= 45:
        return 'D', 2
    elif score >= 40:
        return 'E', 1
    else:
        return 'F', 0

def degree_class(cgpa):
    if cgpa >= 4.5:
        return '1st Class'
    elif cgpa >= 3.5:
        return '2nd Class Upper'
    elif cgpa >= 2.5:
        return '2nd Class Lower'
    elif cgpa >= 1.5:
        return '3rd Class'
    else:
        return 'Pass'

def calculate_cgpa(semesters_data):
    TNU = 0
    TCP = 0
    all_courses = []

    for sem_index, semester in enumerate(semesters_data):
        sem_tnu = 0
        sem_tcp = 0
        courses = []

        for course in semester['courses']:
            score       = int(course['score'])
            course_unit = int(course['unit'])
            grade, point = get_grade_and_point(score)
            credit_point = course_unit * point

            sem_tnu += course_unit
            sem_tcp += credit_point
            TNU     += course_unit
            TCP     += credit_point

            courses.append({
                'code':         course['code'],
                'title':        course['title'],
                'unit':         course_unit,
                'score':        score,
                'grade':        grade,
                'point':        point,
                'credit_point': credit_point,
            })

        sem_gpa = round(sem_tcp / sem_tnu, 2) if sem_tnu > 0 else 0
        all_courses.append({
            'semester': sem_index + 1,
            'courses':  courses,
            'gpa':      sem_gpa,
            'tnu':      sem_tnu,
            'tcp':      sem_tcp,
        })

    cgpa = round(TCP / TNU, 2) if TNU > 0 else 0
    return {
        'cgpa':         cgpa,
        'degree_class': degree_class(cgpa),
        'total_units':  TNU,
        'total_points': TCP,
        'semesters':    all_courses,
    }

# ── PDF Generator ─────────────────────────────────────────────────────────────

def generate_pdf(student, result):
    buffer = io.BytesIO()
    doc    = SimpleDocTemplate(buffer, pagesize=A4,
                               rightMargin=2*cm, leftMargin=2*cm,
                               topMargin=2*cm, bottomMargin=2*cm)
    styles = getSampleStyleSheet()
    story  = []

    title_style = ParagraphStyle('Title', parent=styles['Normal'],
                                 fontSize=18, fontName='Helvetica-Bold',
                                 alignment=TA_CENTER, spaceAfter=4)
    sub_style   = ParagraphStyle('Sub', parent=styles['Normal'],
                                 fontSize=11, alignment=TA_CENTER,
                                 textColor=colors.HexColor('#555555'), spaceAfter=2)

    story.append(Paragraph("CGPA Result Report", title_style))
    story.append(Paragraph("Academic Performance Summary", sub_style))
    story.append(Paragraph(f"Generated: {datetime.now().strftime('%B %d, %Y %I:%M %p')}", sub_style))
    story.append(HRFlowable(width="100%", thickness=1.5,
                            color=colors.HexColor('#2563eb'), spaceAfter=12))

    info_data = [
        [Paragraph('<b>Name:</b>', styles['Normal']),       Paragraph(student['name'], styles['Normal']),
         Paragraph('<b>Matric No:</b>', styles['Normal']),  Paragraph(student['matric_no'], styles['Normal'])],
        [Paragraph('<b>Department:</b>', styles['Normal']), Paragraph(student['department'], styles['Normal']),
         Paragraph('<b>Programme:</b>', styles['Normal']),  Paragraph(student.get('programme', '-'), styles['Normal'])],
        [Paragraph('<b>Faculty:</b>', styles['Normal']),    Paragraph(student.get('faculty', '-'), styles['Normal']),
         Paragraph('<b>Level:</b>', styles['Normal']),      Paragraph(student.get('level', '-'), styles['Normal'])],
    ]
    info_table = Table(info_data, colWidths=[3.5*cm, 6*cm, 3.5*cm, 4*cm])
    info_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#f0f4ff')),
        ('ROWBACKGROUNDS', (0,0), (-1,-1), [colors.HexColor('#f0f4ff'), colors.HexColor('#ffffff')]),
        ('GRID', (0,0), (-1,-1), 0.3, colors.HexColor('#cccccc')),
        ('PADDING', (0,0), (-1,-1), 6),
        ('FONTSIZE', (0,0), (-1,-1), 10),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(info_table)
    story.append(Spacer(1, 16))

    for sem in result['semesters']:
        story.append(Paragraph(f"Semester {sem['semester']}", ParagraphStyle(
            'SemHead', parent=styles['Normal'], fontSize=12,
            fontName='Helvetica-Bold', spaceAfter=6,
            textColor=colors.HexColor('#1e40af'))))

        headers   = [['Course Code', 'Course Title', 'Unit', 'Score', 'Grade', 'Point', 'Credit Point']]
        rows      = [[c['code'], c['title'], c['unit'], c['score'],
                      c['grade'], c['point'], c['credit_point']] for c in sem['courses']]
        total_row = [['', 'SEMESTER TOTAL', sem['tnu'], '', '', '', sem['tcp']]]
        gpa_row   = [['', f"Semester GPA: {sem['gpa']}", '', '', '', '', '']]

        table_data = headers + rows + total_row + gpa_row
        col_widths = [2.8*cm, 6*cm, 1.5*cm, 1.5*cm, 1.5*cm, 1.5*cm, 2.2*cm]
        t = Table(table_data, colWidths=col_widths)
        t.setStyle(TableStyle([
            ('BACKGROUND',    (0,0),  (-1,0),  colors.HexColor('#1e40af')),
            ('TEXTCOLOR',     (0,0),  (-1,0),  colors.white),
            ('FONTNAME',      (0,0),  (-1,0),  'Helvetica-Bold'),
            ('FONTSIZE',      (0,0),  (-1,-1), 9),
            ('ALIGN',         (0,0),  (-1,-1), 'CENTER'),
            ('ALIGN',         (1,0),  (1,-1),  'LEFT'),
            ('ROWBACKGROUNDS',(0,1),  (-1,-3), [colors.white, colors.HexColor('#f8faff')]),
            ('BACKGROUND',    (0,-2), (-1,-2), colors.HexColor('#dbeafe')),
            ('FONTNAME',      (0,-2), (-1,-2), 'Helvetica-Bold'),
            ('BACKGROUND',    (0,-1), (-1,-1), colors.HexColor('#eff6ff')),
            ('FONTNAME',      (0,-1), (-1,-1), 'Helvetica-Bold'),
            ('TEXTCOLOR',     (0,-1), (-1,-1), colors.HexColor('#1e40af')),
            ('ALIGN',         (1,-1), (1,-1),  'LEFT'),
            ('GRID',          (0,0),  (-1,-1), 0.3, colors.HexColor('#cccccc')),
            ('PADDING',       (0,0),  (-1,-1), 5),
            ('VALIGN',        (0,0),  (-1,-1), 'MIDDLE'),
        ]))
        story.append(t)
        story.append(Spacer(1, 14))

    story.append(HRFlowable(width="100%", thickness=1.5,
                            color=colors.HexColor('#2563eb'), spaceBefore=4, spaceAfter=10))

    summary_data = [
        ['Total Credit Units',  str(result['total_units'])],
        ['Total Credit Points', str(result['total_points'])],
        ['CGPA',                str(result['cgpa'])],
        ['Degree Class',        result['degree_class']],
    ]
    summary_table = Table(summary_data, colWidths=[8*cm, 9*cm])
    summary_table.setStyle(TableStyle([
        ('FONTSIZE',      (0,0),  (-1,-1), 11),
        ('FONTNAME',      (0,0),  (0,-1),  'Helvetica-Bold'),
        ('FONTNAME',      (1,2),  (1,3),   'Helvetica-Bold'),
        ('TEXTCOLOR',     (1,2),  (1,3),   colors.HexColor('#1e40af')),
        ('FONTSIZE',      (1,2),  (1,3),   13),
        ('BACKGROUND',    (0,2),  (-1,3),  colors.HexColor('#dbeafe')),
        ('ROWBACKGROUNDS',(0,0),  (-1,1),  [colors.HexColor('#f8faff'), colors.white]),
        ('GRID',          (0,0),  (-1,-1), 0.3, colors.HexColor('#cccccc')),
        ('PADDING',       (0,0),  (-1,-1), 8),
        ('VALIGN',        (0,0),  (-1,-1), 'MIDDLE'),
    ]))
    story.append(summary_table)

    doc.build(story)
    buffer.seek(0)
    return buffer

# ── Admin Report PDF Generator ────────────────────────────────────────────────

def generate_admin_pdf(data):
    buffer = io.BytesIO()
    doc    = SimpleDocTemplate(buffer, pagesize=A4,
                               rightMargin=2*cm, leftMargin=2*cm,
                               topMargin=2*cm, bottomMargin=2*cm)
    styles = getSampleStyleSheet()
    story  = []

    title_style = ParagraphStyle('Title', parent=styles['Normal'],
                                 fontSize=20, fontName='Helvetica-Bold',
                                 alignment=TA_CENTER, spaceAfter=4)
    sub_style   = ParagraphStyle('Sub', parent=styles['Normal'],
                                 fontSize=11, alignment=TA_CENTER,
                                 textColor=colors.HexColor('#555555'), spaceAfter=2)
    section_style = ParagraphStyle('Section', parent=styles['Normal'],
                                   fontSize=13, fontName='Helvetica-Bold',
                                   textColor=colors.HexColor('#1e40af'),
                                   spaceBefore=14, spaceAfter=8)

    story.append(Paragraph("CGPA Admin Report", title_style))
    story.append(Paragraph("Full Student Academic Performance Summary", sub_style))
    story.append(Paragraph(f"Generated: {datetime.now().strftime('%B %d, %Y %I:%M %p')}", sub_style))
    story.append(HRFlowable(width="100%", thickness=1.5,
                            color=colors.HexColor('#1e40af'), spaceAfter=14))

    stats = data.get('stats', {})
    story.append(Paragraph("Summary Statistics", section_style))

    stat_data = [
        ['Total Students', 'Results Calculated', 'Average CGPA', 'Top Department'],
        [
            str(stats.get('total_students', '-')),
            str(stats.get('total_results',  '-')),
            str(stats.get('avg_cgpa',       '-')),
            str(stats.get('top_dept',       '-')),
        ]
    ]
    stat_table = Table(stat_data, colWidths=[4*cm, 4*cm, 4*cm, 5*cm])
    stat_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0),  colors.HexColor('#1e40af')),
        ('TEXTCOLOR',  (0,0), (-1,0),  colors.white),
        ('FONTNAME',   (0,0), (-1,0),  'Helvetica-Bold'),
        ('FONTSIZE',   (0,0), (-1,-1), 10),
        ('ALIGN',      (0,0), (-1,-1), 'CENTER'),
        ('FONTNAME',   (0,1), (-1,1),  'Helvetica-Bold'),
        ('FONTSIZE',   (0,1), (-1,1),  14),
        ('TEXTCOLOR',  (0,1), (-1,1),  colors.HexColor('#1e40af')),
        ('BACKGROUND', (0,1), (-1,1),  colors.HexColor('#f0f4ff')),
        ('GRID',       (0,0), (-1,-1), 0.3, colors.HexColor('#cccccc')),
        ('PADDING',    (0,0), (-1,-1), 10),
        ('VALIGN',     (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(stat_table)
    story.append(Spacer(1, 16))

    story.append(Paragraph("All Students", section_style))

    headers  = [['#', 'Name', 'Matric No', 'Department', 'CGPA', 'Degree Class', 'Status']]
    rows     = []
    students = data.get('students', [])
    for i, s in enumerate(students, 1):
        cgpa   = str(s.get('cgpa', '')) if s.get('cgpa') else '-'
        degree = s.get('degree_class', '-') or '-'
        status = 'Calculated' if s.get('cgpa') else 'Pending'
        rows.append([
            str(i),
            s.get('name', ''),
            s.get('matric_no', ''),
            s.get('department', ''),
            cgpa,
            degree,
            status,
        ])

    table_data = headers + rows
    col_widths = [1*cm, 4.5*cm, 3*cm, 3.5*cm, 1.8*cm, 3.2*cm, 2*cm]
    t = Table(table_data, colWidths=col_widths, repeatRows=1)
    t.setStyle(TableStyle([
        ('BACKGROUND',    (0,0), (-1,0),  colors.HexColor('#1e40af')),
        ('TEXTCOLOR',     (0,0), (-1,0),  colors.white),
        ('FONTNAME',      (0,0), (-1,0),  'Helvetica-Bold'),
        ('FONTSIZE',      (0,0), (-1,-1), 9),
        ('ALIGN',         (0,0), (-1,-1), 'CENTER'),
        ('ALIGN',         (1,1), (3,-1),  'LEFT'),
        ('ROWBACKGROUNDS',(0,1), (-1,-1), [colors.white, colors.HexColor('#f8faff')]),
        ('GRID',          (0,0), (-1,-1), 0.3, colors.HexColor('#cccccc')),
        ('PADDING',       (0,0), (-1,-1), 6),
        ('VALIGN',        (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(t)

    story.append(Spacer(1, 20))
    story.append(HRFlowable(width="100%", thickness=0.5,
                            color=colors.HexColor('#cccccc'), spaceAfter=8))
    story.append(Paragraph(
        f"This report was automatically generated by the CGPA Calculator System on {datetime.now().strftime('%B %d, %Y')}.",
        ParagraphStyle('Footer', parent=styles['Normal'], fontSize=8,
                       textColor=colors.HexColor('#999999'), alignment=TA_CENTER)
    ))

    doc.build(story)
    buffer.seek(0)
    return buffer

# ── API Routes ────────────────────────────────────────────────────────────────

@app.route('/calculate', methods=['POST'])
def calculate():
    data = request.get_json()
    if not data:
        return jsonify({'error': 'No data received'}), 400
    try:
        result = calculate_cgpa(data['semesters'])
        return jsonify(result)
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/download_pdf', methods=['POST'])
def download_pdf():
    data = request.get_json()
    if not data:
        return jsonify({'error': 'No data received'}), 400
    try:
        result   = calculate_cgpa(data['semesters'])
        student  = data['student']
        buffer   = generate_pdf(student, result)
        filename = f"CGPA_Report_{student['matric_no'].replace('/', '_')}.pdf"
        return send_file(buffer, as_attachment=True,
                         download_name=filename, mimetype='application/pdf')
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/admin_report', methods=['POST'])
def admin_report():
    data = request.get_json()
    if not data:
        return jsonify({'error': 'No data received'}), 400
    try:
        buffer   = generate_admin_pdf(data)
        filename = f"Admin_CGPA_Report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
        return send_file(buffer, as_attachment=True,
                         download_name=filename, mimetype='application/pdf')
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/ping', methods=['GET'])
def ping():
    return jsonify({'status': 'running'})

if __name__ == '__main__':
    app.run(debug=True, port=5000)